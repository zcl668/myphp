<?php
/**
 * 河南台直播防盗链绕过（后端代理版）
 * 完整单文件，零依赖，PHP ≥ 7.0
 * Author: 沐辰
 */

/* ========================== 1. 频道号对照表 ========================== */
$channels = [
    'hnws' => 145, 'hnds' => 141, 'hnms' => 146, 'hmfz' => 147,
    'hndsj' => 148, 'hnxw' => 149, 'htgw' => 150, 'hngg' => 151,
    'hnxc' => 152, 'hnly' => 154, 'wwbk' => 155, 'wspd' => 156,
    'hnqy' => 157, 'ydxj' => 163, 'xsj' => 183, 'gxpd' => 194,
    'zz1' => 197, 'kf1' => 198, 'ly1' => 204, 'pds1' => 205,
    'ay1' => 206, 'hb1' => 207, 'xx1' => 208, 'jz1' => 209,
    'py1' => 219, 'xc1' => 220, 'lh1' => 221, 'smx1' => 222,
    'ny1' => 223, 'sq1' => 224, 'xy1' => 225, 'zk1' => 226,
    'zmd1' => 227, 'jy1' => 228,
];

/* ========================== 2. 获取真实 m3u8 ========================== */
function getRealM3u8(string $id, array $channels): ?string
{
    $channelId = $channels[$id] ?? $channels['hnws'];
    $t  = time();
    $sign = hash('sha256', '6ca114a836ac7d73' . $t);
    $url  = "https://pubmod.hntv.tv/program/getAuth/channel/channelIds/1/{$channelId}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ["timestamp: {$t}", "sign: {$sign}"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $json = curl_exec($ch);
    curl_close($ch);

    if ($json) {
        $arr = json_decode($json, true);
        return $arr[0]['video_streams'][0] ?? null;
    }
    return null;
}

/* ========================== 3. 代理 .ts 切片 ========================== */
function proxySlice(string $realHost, string $path): void
{
    $cookieFile = sys_get_temp_dir() . '/hntv.cookie';
    $sliceUrl = 'https://' . $realHost . '/' . ltrim($path, '/');

    $ch = curl_init($sliceUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 0,          // 直接输出
        CURLOPT_HTTPHEADER     => ['Referer: https://www.hntv.tv/'],
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
    exit;
}

/* ========================== 4. 路由 ========================== */
$id = $_GET['id'] ?? 'hnws';

// 如果是切片代理请求
if (isset($_GET['path'])) {
    $realHost = $_GET['host'] ?? 'tvcdn.stream3.hndt.com';
    proxySlice($realHost, $_GET['path']);
}

// 否则：获取真实 m3u8 → 清洗 → 返回
$realM3u8 = getRealM3u8($id, $channels);
if (!$realM3u8) {
    http_response_code(500);
    exit('#EXTM3U' . PHP_EOL . '#获取失败');
}

$realHost = parse_url($realM3u8, PHP_URL_HOST);
$content = file_get_contents($realM3u8);
if (!$content) {
    http_response_code(502);
    exit('#EXTM3U' . PHP_EOL . '#下载失败');
}

// 把切片地址改写成本地代理
$content = preg_replace(
    '#^(/[^/\r\n]+\.ts)$#m',
    '/hntv-proxy.php?host=' . $realHost . '&path=$1',
    $content
);

header('Content-Type: application/vnd.apple.mpegurl');
header('Cache-Control: no-cache');
echo $content;