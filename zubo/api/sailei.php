<?php
// Vercel 环境适配版 - 无缓存、无文件写入

error_reporting(0);
ini_set('display_errors', 0);

$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';
$requiredHeaders = [
    'User-Agent: ' . $userAgent,
    'Referer: https://sailei.dpdns.org/',
    'Origin: https://sailei.dpdns.org'
];

$channelId = isset($_GET['id']) ? trim($_GET['id']) : '';
$proxyUrl = isset($_GET['url']) ? trim($_GET['url']) : '';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$currentScriptUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

// 直接获取频道数据（无缓存）
function getOriginalChannelData() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sailei.dpdns.org/api/m3u/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: */*',
        'accept-language: en-US,en;q=0.9,zh-CN;q=0.8,zh;q=0.7',
        'referer: https://sailei.dpdns.org/',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
    ]);
    curl_setopt($ch, CURLOPT_COOKIE, '_pk_id.1.8387=94566b0d111f84e5.1770874654.; _pk_ses.1.8387=1');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response ?: '[]';
}

// 获取播放地址
function getStreamUrl($channelId) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sailei.dpdns.org/api/altcha');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $challengeJson = curl_exec($ch);
    curl_close($ch);
    
    if (!$challengeJson) return null;
    
    $data = json_decode($challengeJson, true);
    if (!isset($data['challenge']) || !isset($data['salt'])) return null;
    
    $challenge = $data['challenge'];
    $salt = str_replace('&amp;', '&', $data['salt']);
    $maxNumber = $data['maxnumber'] ?? 100000;
    $algorithm = $data['algorithm'] ?? 'SHA-256';
    $signature = $data['signature'] ?? '';
    
    // 暴力破解验证码
    $solvedNumber = null;
    for ($i = 0; $i <= $maxNumber; $i++) {
        if (hash('sha256', $salt . $i) === $challenge) {
            $solvedNumber = $i;
            break;
        }
    }
    
    if ($solvedNumber === null) return null;
    
    $payload = [
        "algorithm" => $algorithm,
        "challenge" => $challenge,
        "number" => $solvedNumber,
        "salt" => $salt,
        "signature" => $signature,
        "took" => 0
    ];
    $token = base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://sailei.dpdns.org/api/checkout?channelId={$channelId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'referer: https://sailei.dpdns.org/play.html?channelId=' . $channelId,
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'x-altcha-token: ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_COOKIE, 'userId=' . (time() * 1000));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (isset($result['success']) && $result['success'] === true && isset($result['streamUrl'])) {
        return $result['streamUrl'];
    }
    return null;
}

// 生成M3U播放列表
function generateM3uPlaylistFromJson($jsonContent) {
    global $currentScriptUrl;
    
    $data = json_decode($jsonContent, true);
    if (!is_array($data)) {
        return "#EXTM3U\n#EXTINF:-1,Error\n";
    }
    
    $output = "#EXTM3U\n";
    foreach ($data as $group) {
        $groupTitle = $group['title'] ?? 'Unknown';
        if ($groupTitle === '请立即更新') continue;
        
        foreach ($group['items'] ?? [] as $item) {
            $channelName = $item['name'] ?? 'Unknown';
            $channelId = $item['channelId'] ?? '';
            $iconUrl = $item['icon_url'] ?? '';
            
            if (empty($channelId)) continue;
            
            $logoAttr = $iconUrl ? ' tvg-logo="' . $iconUrl . '"' : '';
            $output .= '#EXTINF:-1 tvg-id="' . $channelName . '" group-title="' . $groupTitle . '"' . $logoAttr . ',' . $channelName . "\n";
            $output .= $currentScriptUrl . '?id=' . rawurlencode($channelId) . "\n";
        }
    }
    return $output;
}

// 代理M3U8
function proxyM3u8($url) {
    global $currentScriptUrl;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Referer: https://sailei.dpdns.org/',
            'Origin: https://sailei.dpdns.org'
        ]
    ]);
    $content = curl_exec($ch);
    curl_close($ch);
    
    if (!$content) {
        header('HTTP/1.1 502 Bad Gateway');
        echo 'Failed to fetch M3U8';
        return;
    }
    
    $lines = explode("\n", $content);
    $output = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || $trimmed[0] === '#') {
            $output[] = $line;
        } elseif (strpos($trimmed, 'http') === 0 || strpos($trimmed, '/') === 0) {
            $output[] = $currentScriptUrl . '?url=' . urlencode($trimmed);
        } else {
            $baseUrl = dirname($url);
            $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($trimmed, '/');
            $output[] = $currentScriptUrl . '?url=' . urlencode($fullUrl);
        }
    }
    
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Cache-Control: public, max-age=30');
    echo implode("\n", $output);
}

// 代理TS
function proxyTsStream($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Referer: https://sailei.dpdns.org/',
            'Origin: https://sailei.dpdns.org'
        ],
        CURLOPT_WRITEFUNCTION => function($ch, $data) {
            echo $data;
            return strlen($data);
        }
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// 主路由
if (!empty($proxyUrl)) {
    $decodedUrl = urldecode($proxyUrl);
    if (strpos($decodedUrl, '.ts') !== false) {
        proxyTsStream($decodedUrl);
    } else {
        proxyM3u8($decodedUrl);
    }
} elseif (!empty($channelId)) {
    $jsonContent = getOriginalChannelData();
    $data = json_decode($jsonContent, true);
    
    $streamUrl = null;
    if (is_array($data)) {
        foreach ($data as $group) {
            foreach ($group['items'] ?? [] as $item) {
                if (isset($item['channelId']) && $item['channelId'] === $channelId) {
                    $streamUrl = getStreamUrl($channelId);
                    break 2;
                }
            }
        }
    }
    
    if ($streamUrl) {
        proxyM3u8($streamUrl);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo 'Channel not found or stream unavailable';
    }
} else {
    $jsonContent = getOriginalChannelData();
    header('Content-Type: text/plain; charset=utf-8');
    echo generateM3uPlaylistFromJson($jsonContent);
}
?>