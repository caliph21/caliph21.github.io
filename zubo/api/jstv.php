<?php
error_reporting(0);
ini_set('display_errors', 0);

// 频道映射
$map = [
    'jsws'   => 'jswspro',
    'jsws4k' => 'jsws4kpro',
    'jscs'   => 'jscspro',
    'jszy'   => 'jszypro',
    'jsys'   => 'jsyspro',
    'jsxw'   => 'jsxwpro',
    'jsjy'   => 'jsjypro',
    'jsty'   => 'jsxxpro',
    'jsgj'   => 'jsgjpro',
    'ymkt'   => 'ymktpro',
];

$id = isset($_GET['id']) ? $_GET['id'] : '';
if (isset($map[$id])) {
    $channelKey = $map[$id];
} elseif (in_array($id, $map)) {
    $channelKey = $id;
} else {
    $channelKey = 'jswspro';
}

// 生成签名
$txTime = dechex(time() + 180);
$txSecret = md5("HCPMPKxQNrKAyjzR67JG" . $channelKey . $txTime);
$m3u8Url = "https://litchi-play-encrypted-site.jstv.com/applive/{$channelKey}.m3u8?txSecret={$txSecret}&txTime={$txTime}";

// 获取真实协议和主机（适配 Vercel 代理）
$protocol = 'https'; // Vercel 强制 HTTPS
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'itv.1920.qzz.io';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api/jstv.php';
$scriptUrl = $protocol . "://" . $host . $scriptName;

// 处理 TS 请求
if (isset($_GET['ts'])) {
    $tsUrl = urldecode($_GET['ts']);
    $tsData = fetchWithHeaders($tsUrl);
    if ($tsData) {
        header('Content-Type: video/MP2T');
        echo $tsData;
    } else {
        header('HTTP/1.1 502 Bad Gateway');
        echo 'TS fetch failed';
    }
    exit;
}

// 获取 M3U8
$m3u8Content = fetchWithHeaders($m3u8Url);
if (!$m3u8Content) {
    http_response_code(502);
    echo 'Failed to fetch M3U8';
    exit;
}

// 重写 TS 路径
$baseUrl = dirname($m3u8Url) . '/';
$lines = explode("\n", $m3u8Content);
$output = [];
foreach ($lines as $line) {
    $line = rtrim($line);
    if (strpos($line, '#') === 0 || trim($line) === '') {
        $output[] = $line;
        continue;
    }
    if (preg_match('/^([^?#]+\.ts)(\?.*)?$/', $line, $matches)) {
        $tsFile = $matches[1];
        $query = isset($matches[2]) ? $matches[2] : '';
        $fullTsUrl = $baseUrl . $tsFile . $query;
        $output[] = $scriptUrl . '?ts=' . urlencode($fullTsUrl);
    } else {
        $output[] = $line;
    }
}

header('Content-Type: application/vnd.apple.mpegurl');
header('Cache-Control: public, max-age=30');
echo implode("\n", $output);

function fetchWithHeaders($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Mobile Safari/537.36',
            'Accept: */*',
            'Origin: https://live.jstv.com',
            'X-Requested-With: mark.via',
            'Referer: https://live.jstv.com/',
            'Accept-Language: zh-CN,zh;q=0.9',
            'Accept-Encoding: gzip, deflate',
        ],
        CURLOPT_ENCODING => '',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode === 200) ? $response : false;
}
?>