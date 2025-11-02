<?php
// 定义允许的ID和对应的签名
const ALLOWED_IDS = [
    "1722331404917192" => "1327864dad3204c22f44fd383944e295",//磐安新闻综合频道
    "1722333472632211" => "860dc6f242c675a6c54b5b54d4762a6c",//磐安文化旅游频道
];

// 获取并验证ID参数
$id = $_GET["id"] ?? '';

if (!isset(ALLOWED_IDS[$id])) {
    http_response_code(400);
    exit('Invalid ID parameter');
}

// 配置请求参数
$url = "http://www.qukanvideo.com/h5/channel/view/item/AntiTheft/playUrl";
$body = http_build_query([
    'source' => 'web',
    'liveId' => $id,
    'sign' => ALLOWED_IDS[$id]
]);

// 初始化cURL
$ch = curl_init();
$options = [
    CURLOPT_URL => $url,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]
];
curl_setopt_array($ch, $options);

// 执行请求
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// 处理响应
if ($response === false) {
    http_response_code(500);
    exit('cURL Error: ' . $error);
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    exit('API request failed with HTTP code: ' . $httpCode);
}

// 解析JSON响应
$data = json_decode($response);
if ($data === null || !isset($data->value->url)) {
    http_response_code(500);
    exit('Invalid API response');
}

// 重定向到播放URL
header("Location: " . $data->value->url);
exit;
?>