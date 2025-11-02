<?php
// 食用方法：访问 http://服务器地址[:端口]/gz.php?list 获取频道列表
ini_set('output_buffering', 'off');

$idMapping = [
    'gzzh'   => '31',  // 综合频道
    'gzxw'   => '32',  // 新闻频道
    'gzngds' => '33',  // 南国都市频道
    'gzfz'   => '34',  // 法治频道
    'gzys'   => '36'   // 影视频道
];

if (isset($_GET['list'])) {
    // 获取频道数据
    $apiUrl = 'https://gzbn.gztv.com:7443/plus-cloud-manage-app/liveChannel/queryLiveChannelList?type=1';
    $response = @file_get_contents($apiUrl);
    
    if ($response === false) {
        http_response_code(502);
        header('Content-Type: text/plain; charset=utf-8');
        die("频道列表获取失败");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE 
        || !isset($data['data']) 
        || !is_array($data['data'])
        || $data['code'] != 200) {
        http_response_code(502);
        header('Content-Type: text/plain; charset=utf-8');
        die("无效的频道数据格式");
    }

    // 构建频道索引
    $channelIndex = [];
    foreach ($data['data'] as $channel) {
        if (isset($channel['stationNumber'])) {
            $channelIndex[strval($channel['stationNumber'])] = $channel;
        }
    }

    // 生成动态基础URL
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'];
    
    // 判断是否默认端口
    $isDefaultPort = ($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443);
    $hostHeader = $isDefaultPort ? $host : $host.':'.$port;
    
    // 获取请求路径
    $requestPath = strtok($_SERVER['REQUEST_URI'], '?');
    $baseUrl = "{$scheme}://{$hostHeader}{$requestPath}";

    // 构建频道列表
    $output = [];
    foreach ($idMapping as $alias => $station) {
        if (isset($channelIndex[$station])) {
            $channel = $channelIndex[$station];
            $output[] = sprintf("%s,%s?id=%s",
                $channel['name'],
                $baseUrl,
                $alias
            );
        }
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: max-age=300, public');
    echo implode("\n", $output);
    exit;
}

// 处理频道跳转
$channelAlias = strtolower($_GET['id'] ?? '');
if (!isset($idMapping[$channelAlias])) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    die("无效频道标识，可用值：" . implode(', ', array_keys($idMapping)));
}

$stationNumber = $idMapping[$channelAlias];
$apiUrl = 'https://gzbn.gztv.com:7443/plus-cloud-manage-app/liveChannel/queryLiveChannelList?type=1';
$response = @file_get_contents($apiUrl);

if ($response === false) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    die("频道数据获取失败");
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE 
    || !isset($data['data']) 
    || !is_array($data['data'])
    || $data['code'] != 200) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    die("无效的频道数据格式");
}

$targetUrl = null;
foreach ($data['data'] as $channel) {
    if (isset($channel['stationNumber']) 
        && strval($channel['stationNumber']) === $stationNumber
        && !empty($channel['httpUrl'])) {
        $targetUrl = $channel['httpUrl'];
        break;
    }
}

if ($targetUrl) {
    header("Location: $targetUrl", true, 302);
    header('Cache-Control: no-cache');
    exit();
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
die("该频道暂时不可用");
?>