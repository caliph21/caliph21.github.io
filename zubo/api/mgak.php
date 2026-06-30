<?php
//本算法首发于直播源论坛：https://bbs.livecodes.vip/，转载或转化为JS或发至其他地方，请注明出处，否则生个孩子没屁眼
/**
 * PHP 7.4 版本：模拟登录并请求 PlayChannel。
 *
 * GET 参数：
 * - id    可选，映射 channelID，默认 265667645
 * - type  可选，映射 businessType，默认 BTV
 * - mid   可选，映射 mediaID；若不传则内部按 id + 1 自动生成
 * - json  可选，show=输出 JSON；默认 noshow（302 跳转到 playURL）
 * - force        可选，1=强制重登
 * - phone        可选，11位手机号（不传走游客）
 * - debug        可选，1=返回调试信息
 *
 * 缓存（APCu）：
 * - 登录态缓存 key: migu:login_state，TTL=1200秒
 * - playURL 缓存 key: migu:playurl:{channelID}，TTL=600秒
 */

const DEFAULT_UA = 'Dalvik/2.1.0 (Linux; U; Android 15; XIAOMI-15 Build/TP1A.220624.014)';
// 修改：直接使用目标登录地址，跳过302重定向
const DEFAULT_EDS_LOGIN_URL = 'http://vsc.aikan.miguvideo.com:7113/EPG/JSON/Login';
const DEFAULT_GETTIME_URL = 'http://aikanvod.miguvideo.com/video/p/getTime.jsp?vt=9';
const DEFAULT_BUSINESS_TYPE = 'BTV';
const DEFAULT_CHANNEL_ID = '265667645';

const LOGIN_STATE_CACHE_KEY = 'migu:login_state';
const LOGIN_STATE_TTL = 1200;

const PLAYURL_CACHE_PREFIX = 'migu:playurl:';
const PLAYURL_TTL = 600;

const AUTH_EXPIRED_CODES = [
    '-2',
    '125023001',
    '125023002',
    '125023003',
    '125023004',
    '125023005',
    '125023006',
    '125023007',
    '125023008',
    '125023009',
    '125023010',
    '125023011',
    '125023012',
];

if (PHP_SAPI === 'cli' && empty($_GET) && isset($argv[1])) {
    parse_str($argv[1], $_GET);
}

function respond_json(array $payload, int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), PHP_EOL;
    exit;
}

function respond_redirect(string $url, int $status = 302): void
{
    $safeUrl = str_replace(["\r", "\n"], '', trim($url));
    if ($safeUrl === '') {
        respond_json([
            'ok' => false,
            'error' => '无法跳转：playURL 为空',
        ], 500);
    }
    if (PHP_SAPI === 'cli') {
        echo $safeUrl, PHP_EOL;
        exit;
    }
    if (!headers_sent()) {
        header('Location: ' . $safeUrl, true, $status);
    }
    exit;
}

function require_apcu(): void
{
    if (!function_exists('apcu_fetch') || !filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)) {
        respond_json([
            'ok' => false,
            'error' => 'APCu 不可用：请确认已安装并启用 apcu 扩展。',
        ], 500);
    }
}

function normalize_cookie_pair(string $rawSetCookie): string
{
    if ($rawSetCookie === '') {
        return '';
    }
    $parts = explode(';', $rawSetCookie, 2);
    return trim($parts[0]);
}

function pick_ret_code(array $data): string
{
    foreach (['retCode', 'retcode', 'realStrRetCode'] as $k) {
        if (isset($data[$k]) && is_string($data[$k]) && $data[$k] !== '') {
            return $data[$k];
        }
    }
    if (isset($data['result']) && is_array($data['result'])) {
        foreach (['retCode', 'retcode'] as $k) {
            if (isset($data['result'][$k]) && is_string($data['result'][$k]) && $data['result'][$k] !== '') {
                return $data['result'][$k];
            }
        }
    }
    return '';
}

function pick_ret_msg(array $data): string
{
    foreach (['retMsg', 'retmsg', 'message', 'msg'] as $k) {
        if (isset($data[$k]) && is_string($data[$k]) && $data[$k] !== '') {
            return $data[$k];
        }
    }
    if (isset($data['result']) && is_array($data['result'])) {
        foreach (['retMsg', 'retmsg', 'message', 'msg'] as $k) {
            if (isset($data['result'][$k]) && is_string($data['result'][$k]) && $data['result'][$k] !== '') {
                return $data['result'][$k];
            }
        }
    }
    return '';
}

function need_relogin(int $statusCode, string $retCode, string $retMsg): bool
{
    if ($statusCode === 401 || $statusCode === 403) {
        return true;
    }
    if (in_array($retCode, AUTH_EXPIRED_CODES, true)) {
        return true;
    }
    $lower = strtolower($retMsg);
    foreach (['session', 'login', 'authenticate', 'expired', 'epgsession'] as $hint) {
        if (strpos($lower, $hint) !== false) {
            return true;
        }
    }
    return false;
}

function plus_one_numeric_string(string $n): string
{
    if ($n === '' || !preg_match('/^\d+$/', $n)) {
        throw new RuntimeException('channelID 必须是纯数字字符串，当前值: ' . $n);
    }
    $chars = str_split($n);
    $carry = 1;
    for ($i = count($chars) - 1; $i >= 0; $i--) {
        $digit = ord($chars[$i]) - 48 + $carry;
        if ($digit >= 10) {
            $chars[$i] = '0';
            $carry = 1;
        } else {
            $chars[$i] = chr($digit + 48);
            $carry = 0;
            break;
        }
    }
    if ($carry === 1) {
        array_unshift($chars, '1');
    }
    return implode('', $chars);
}

function http_request(
    string $method,
    string $url,
    array $headers,
    ?string $body,
    int $timeout = 10
): array {
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init 失败'];
    }

    $headerLines = [];
    foreach ($headers as $name => $value) {
        $headerLines[] = $name . ': ' . $value;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headerLines,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        // 移除 curl_close 以避免 PHP 8.5+ 弃用警告
        return ['ok' => false, 'error' => 'curl_exec 失败: ' . $err];
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $rawHeader = substr($raw, 0, $headerSize);
    $respBody = substr($raw, $headerSize);

    $headersOut = [];
    $setCookies = [];
    $lines = preg_split('/\r\n|\n|\r/', (string) $rawHeader);
    if ($lines !== false) {
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $name = trim($k);
            $value = trim($v);
            if ($name === '') {
                continue;
            }
            $lowerName = strtolower($name);
            if (!isset($headersOut[$lowerName])) {
                $headersOut[$lowerName] = $value;
            }
            if ($lowerName === 'set-cookie') {
                $setCookies[] = $value;
            }
        }
    }

    return [
        'ok' => true,
        'status' => $status,
        'body' => $respBody,
        'headers' => $headersOut,
        'set_cookies' => $setCookies,
    ];
}

// 修改：直接登录，无需处理302重定向
function login_eds(array &$state, string $phone, int $timeout): void
{
    $payload = [];
    if (preg_match('/^\d{11}$/', $phone)) {
        $payload['UserID'] = $phone;
    }
    $headers = [
        'User-Agent' => DEFAULT_UA,
        'Content-Type' => 'application/json; charset=UTF-8',
        'Accept' => '*/*',
        'Connection' => 'keep-alive',
        'isEncrypt' => '0',
    ];
    
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        throw new RuntimeException('JSON 编码失败');
    }
    
    // 直接POST到目标地址
    $resp = http_request('POST', DEFAULT_EDS_LOGIN_URL, $headers, $body, $timeout);
    if (!$resp['ok']) {
        throw new RuntimeException($resp['error']);
    }
    if ((int) $resp['status'] < 200 || (int) $resp['status'] >= 300) {
        throw new RuntimeException('EDS 登录失败: HTTP ' . $resp['status'] . ', body=' . substr((string) $resp['body'], 0, 300));
    }

    $data = json_decode((string) $resp['body'], true);
    if (!is_array($data)) {
        throw new RuntimeException('EDS 返回非 JSON: ' . substr((string) $resp['body'], 0, 300));
    }

    $baseUrl = rtrim((string) ($data['epgurl'] ?? ''), '/');
    $loginUrl = rtrim((string) ($data['epghttpsurl'] ?? $baseUrl), '/');
    if ($baseUrl === '') {
        throw new RuntimeException('EDS 未返回 epgurl');
    }

    $state['base_url'] = $baseUrl;
    $state['login_url'] = $loginUrl;
    $state['set_cookie_raw'] = find_cookie_raw((array) ($resp['set_cookies'] ?? []), 'premsisdn');
}

function authenticate(array &$state, int $timeout): void
{
    $baseUrl = (string) ($state['base_url'] ?? '');
    if ($baseUrl === '') {
        throw new RuntimeException('base_url 为空，请先调用 login_eds');
    }

    $payload = [
        'areaID' => '1',
        'locale' => '1',
        'loginType' => '3',
        'OSVersion' => '13',
        'physicalDeviceID' => '000000000000000',
        'templatelame' => 'default',
        'terminalType' => 'AndroidPhone',
        'terminalVendor' => 'XiaoMi',
        'timeZone' => '+0800',
        'userGroup' => '100',
        'softwareVersion' => '581$0$XM-15',
        'channelInfo' => '00990103',
    ];
    $headers = [
        'User-Agent' => DEFAULT_UA,
        'Content-Type' => 'application/json; charset=UTF-8',
        'Accept' => '*/*',
        'Connection' => 'keep-alive',
        'isEncrypt' => '0',
    ];

    $url = $baseUrl . '/EPG/VPE/PHONE/Authenticate';
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        throw new RuntimeException('Authenticate JSON 编码失败');
    }
    $resp = http_request('POST', $url, $headers, $body, $timeout);
    if (!$resp['ok']) {
        throw new RuntimeException($resp['error']);
    }
    if ((int) $resp['status'] < 200 || (int) $resp['status'] >= 300) {
        throw new RuntimeException('Authenticate 失败: HTTP ' . $resp['status'] . ', body=' . substr((string) $resp['body'], 0, 500));
    }

    $data = json_decode((string) $resp['body'], true);
    if (!is_array($data)) {
        throw new RuntimeException('Authenticate 返回非 JSON: ' . substr((string) $resp['body'], 0, 500));
    }

    $retCode = pick_ret_code($data);
    if ($retCode !== '' && $retCode !== '0' && $retCode !== '000000000') {
        throw new RuntimeException('Authenticate retCode=' . $retCode);
    }

    $sessionId = '';
    if (isset($data['sessionID']) && is_string($data['sessionID'])) {
        $sessionId = trim($data['sessionID']);
    } elseif (isset($data['sessionid']) && is_string($data['sessionid'])) {
        $sessionId = trim($data['sessionid']);
    } elseif (isset($data['result']) && is_array($data['result'])) {
        if (isset($data['result']['sessionID']) && is_string($data['result']['sessionID'])) {
            $sessionId = trim($data['result']['sessionID']);
        } elseif (isset($data['result']['sessionid']) && is_string($data['result']['sessionid'])) {
            $sessionId = trim($data['result']['sessionid']);
        }
    }

    if ($sessionId === '') {
        throw new RuntimeException('未拿到 sessionID/sessionid');
    }
    $state['session_id'] = $sessionId;
}

function refresh_arrayid(array &$state, int $timeout): void
{
    $headers = [
        'User-Agent' => DEFAULT_UA,
        'Accept' => '*/*',
        'Connection' => 'keep-alive',
        'EpgSession' => 'JSESSIONID=' . (string) ($state['session_id'] ?? ''),
        'Location' => (string) ($state['base_url'] ?? ''),
        'Cookie' => build_cookie($state, false),
    ];
    $resp = http_request('GET', DEFAULT_GETTIME_URL, $headers, null, $timeout);
    if (!$resp['ok']) {
        throw new RuntimeException($resp['error']);
    }
    $arr = find_cookie_raw((array) ($resp['set_cookies'] ?? []), 'arrayid');
    if ($arr !== '') {
        $state['arrayid_raw'] = $arr;
    }
}

function run_login_flow(string $phone, int $timeout): array
{
    $state = [
        'base_url' => '',
        'login_url' => '',
        'session_id' => '',
        'set_cookie_raw' => '',
        'arrayid_raw' => '',
    ];
    login_eds($state, $phone, $timeout);
    authenticate($state, $timeout);
    refresh_arrayid($state, $timeout);
    return $state;
}

function play_channel(array $state, string $businessType, string $channelID, string $mediaID, int $timeout): array
{
    $baseUrl = (string) ($state['base_url'] ?? '');
    if ($baseUrl === '') {
        throw new RuntimeException('base_url 为空');
    }
    $parts = parse_url($baseUrl);
    if (!is_array($parts) || empty($parts['host'])) {
        throw new RuntimeException('base_url 解析失败: ' . $baseUrl);
    }
    $host = (string) $parts['host'];
    if (isset($parts['port'])) {
        $host .= ':' . (string) $parts['port'];
    }

    $bodyArr = [
        'IDType' => 0,
        'businessType' => $businessType,
        'channelID' => $channelID,
        'mediaID' => $mediaID,
    ];
    $body = json_encode($bodyArr, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        throw new RuntimeException('PlayChannel JSON 编码失败');
    }

    $headers = [
        'User-Agent' => DEFAULT_UA,
        'isEncrypt' => '0',
        'EpgSession' => 'JSESSIONID=' . (string) ($state['session_id'] ?? ''),
        'Location' => $baseUrl,
        'Cookie' => build_cookie($state, true),
        'Content-Type' => 'application/json; charset=UTF-8',
        'Accept' => '*/*',
        'Host' => $host,
        'Connection' => 'keep-alive',
    ];
    $url = $baseUrl . '/VSP/V3/PlayChannel';
    $resp = http_request('POST', $url, $headers, $body, $timeout);
    if (!$resp['ok']) {
        throw new RuntimeException($resp['error']);
    }
    $data = json_decode((string) $resp['body'], true);
    if (!is_array($data)) {
        $data = [];
    }
    return [
        'status' => (int) $resp['status'],
        'data' => $data,
        'raw_body' => (string) $resp['body'],
    ];
}

function fetch_login_state(bool $force, string $phone, int $timeout, array &$debugLog): array
{
    if (!$force) {
        $ok = false;
        $cached = apcu_fetch(LOGIN_STATE_CACHE_KEY, $ok);
        if ($ok && is_array($cached) && !empty($cached['base_url']) && !empty($cached['session_id'])) {
            $debugLog[] = '[cache] 命中登录态缓存';
            return $cached;
        }
    }
    $debugLog[] = '[login] 重新登录并刷新登录态缓存';
    $state = run_login_flow($phone, $timeout);
    apcu_store(LOGIN_STATE_CACHE_KEY, $state, LOGIN_STATE_TTL);
    return $state;
}

function main(): void
{
    require_apcu();

    $channelID = isset($_GET['id']) ? trim((string) $_GET['id']) : DEFAULT_CHANNEL_ID;
    $businessType = isset($_GET['type']) ? trim((string) $_GET['type']) : DEFAULT_BUSINESS_TYPE;
    $mid = isset($_GET['mid']) ? trim((string) $_GET['mid']) : '';
    $force = isset($_GET['force']) && (string) $_GET['force'] === '1';
    $phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
    $debug = isset($_GET['debug']) && (string) $_GET['debug'] === '1';
    $jsonMode = isset($_GET['json']) ? strtolower(trim((string) $_GET['json'])) : 'noshow';
    if ($jsonMode !== 'show') {
        $jsonMode = 'noshow';
    }
    $timeout = 10;

    if ($channelID === '') {
        $channelID = DEFAULT_CHANNEL_ID;
    }
    if ($businessType === '') {
        $businessType = DEFAULT_BUSINESS_TYPE;
    }

    $debugLog = [];
    $mediaID = ($mid !== '') ? $mid : plus_one_numeric_string($channelID);
    $playCacheKey = PLAYURL_CACHE_PREFIX . $channelID;
    $ok = false;
    $cachedPlayURL = apcu_fetch($playCacheKey, $ok);
    if ($ok && is_string($cachedPlayURL) && $cachedPlayURL !== '') {
        if ($jsonMode !== 'show') {
            respond_redirect($cachedPlayURL, 302);
        }
        $resp = [
            'ok' => true,
            'source' => 'playurl_cache',
            'channelID' => $channelID,
            'businessType' => $businessType,
            'mediaID' => $mediaID,
            'playURL' => $cachedPlayURL,
            'cache_ttl_seconds' => PLAYURL_TTL,
            'json_mode' => $jsonMode,
        ];
        if ($debug) {
            $resp['debug'] = ['[cache] 命中 playURL 缓存'];
        }
        respond_json($resp, 200);
    }

    try {
        $state = fetch_login_state($force, $phone, $timeout, $debugLog);

        $playResp = play_channel($state, $businessType, $channelID, $mediaID, $timeout);
        $retCode = pick_ret_code((array) $playResp['data']);
        $retMsg = pick_ret_msg((array) $playResp['data']);

        if (need_relogin((int) $playResp['status'], $retCode, $retMsg)) {
            $debugLog[] = '[retry] 检测到会话可能失效，自动重登并重试一次';
            $state = run_login_flow($phone, $timeout);
            apcu_store(LOGIN_STATE_CACHE_KEY, $state, LOGIN_STATE_TTL);
            $playResp = play_channel($state, $businessType, $channelID, $mediaID, $timeout);
            $retCode = pick_ret_code((array) $playResp['data']);
            $retMsg = pick_ret_msg((array) $playResp['data']);
        }

        $data = (array) $playResp['data'];
        $playURL = isset($data['playURL']) && is_string($data['playURL']) ? trim($data['playURL']) : '';
        if ($playURL !== '') {
            apcu_store($playCacheKey, $playURL, PLAYURL_TTL);
            $debugLog[] = '[cache] 已写入 playURL 缓存，key=' . $playCacheKey;
            if ($jsonMode !== 'show') {
                respond_redirect($playURL, 302);
            }
        }

        $out = [
            'ok' => true,
            'source' => 'api',
            'channelID' => $channelID,
            'businessType' => $businessType,
            'mediaID' => $mediaID,
            'status' => (int) $playResp['status'],
            'retCode' => $retCode,
            'retMsg' => $retMsg,
            'playURL' => $playURL,
            'playURL_cached' => $playURL !== '',
            'cache_ttl_seconds' => PLAYURL_TTL,
            'json_mode' => $jsonMode,
            'response' => $data,
        ];
        if ($debug) {
            $out['debug'] = $debugLog;
        }
        respond_json($out, 200);
    } catch (Throwable $e) {
        $err = [
            'ok' => false,
            'error' => $e->getMessage(),
            'channelID' => $channelID,
            'businessType' => $businessType,
            'mediaID' => isset($mediaID) ? $mediaID : '',
        ];
        if ($debug) {
            $err['debug'] = $debugLog;
        }
        respond_json($err, 500);
    }
}

main();
