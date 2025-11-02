<?php
date_default_timezone_set('Asia/Shanghai');
#http://你的域名或IP/huya.php?id=房间号
#默认CDN是hscdn，有的房间没有提供hscdn，可以加参数type=json查看支持的CDN：
#http://你的域名或IP/huya.php?id=房间号&type=json
#切换CDN的方法，比如切换至txcdn：
#http://你的域名或IP/huya.php?id=房间号&cdn=txcdn

$id   = isset($_GET['id'])   ? trim((string)$_GET['id'])   : '11342412';
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : 'nojson';
$cdn  = isset($_GET['cdn'])  ? trim((string)$_GET['cdn'])  : 'hscdn';

$cacheDir = __DIR__ . '/huyacache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$cacheFile = $cacheDir . '/' . preg_replace('/[^A-Za-z0-9._-]/', '_', $id) . '.json';
$ttl = 60;

function http_request(string $url, string $method = 'GET', string $flag = 'mobile', ?string $payload = null): string {
    $ch = curl_init();
    $headers = [];
    if ($flag === 'mobile') {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.3 Mobile/15E148 Safari/604.1';
    } elseif ($flag === 'uid') {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'upgrade-insecure-requests: 1';
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36';
    } else {
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36';
    }
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($resp === false) throw new RuntimeException('HTTP error: ' . $err);
    return (string)$resp;
}

function fetchRidCanonical(string $rid): string {
    try {
        $html = http_request('https://www.huya.com/' . rawurlencode($rid), 'GET', 'pc');
        if (preg_match('#<link[^>]*rel="canonical"[^>]*href="https://www\.huya\.com/([^"]+)"#i', $html, $m)) {
            return $m[1];
        }
    } catch (\Throwable $e) {}
    return $rid;
}

function seqid_like_go(): string {
    return sprintf('%.0f', microtime(true) * 10000);
}

function processAnticode(string $anticode, string $uid, string $streamName): string {
    $qr = [];
    parse_str($anticode, $qr);
    $f      = seqid_like_go();
    $wsTime = $qr['wsTime'] ?? '';
    $fmRaw  = base64_decode($qr['fm'] ?? '', true);
    if ($fmRaw === false) $fmRaw = '';
    $fm = str_replace(['$0', '$1', '$2', '$3'], [$uid, $streamName, $f, $wsTime], $fmRaw);
    $parts = [
        'wsSecret=' . md5($fm),
        'wsTime='   . $wsTime,
        'u='        . $uid,
        'seqid='    . $f,
        'txyp='     . ($qr['txyp']    ?? ''),
        'fs='       . ($qr['fs']      ?? ''),
        'sphdcdn='  . ($qr['sphdcdn'] ?? ''),
        'sphdDC='   . ($qr['sphdDC']  ?? ''),
        'sphd='     . ($qr['sphd']    ?? ''),
        'exsphd='   . ($qr['exsphd']  ?? ''),
        'ratio=0',
    ];
    return implode('&', $parts);
}

function getUid(): string {
    $payload = json_encode([
        'appId' => 5002,
        'byPass' => 3,
        'context' => '',
        'version' => '2.4',
        'data' => (object)[],
    ], JSON_UNESCAPED_UNICODE);
    $resp = http_request('https://udblgn.huya.com/web/anonymousLogin', 'POST', 'uid', $payload);
    $j = json_decode($resp, true);
    if (!is_array($j) || !isset($j['data']['uid'])) throw new RuntimeException('failed to get uid');
    return (string)$j['data']['uid'];
}

try {
    $cached = (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) ? json_decode((string)file_get_contents($cacheFile), true) : null;
    if (is_array($cached) && $cached) {
        if ($type === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($cached, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } else {
            $out = $cdn && isset($cached[$cdn]) ? $cached[$cdn] : (reset($cached) ?: '');
            if ($out === '') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'no_flv_stream'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . $out, true, 302);
            exit;
        }
    }

    $rid = fetchRidCanonical($id);
    $roomJson = http_request('https://mp.huya.com/cache.php?m=Live&do=profileRoom&roomid=' . rawurlencode($rid), 'GET', 'mobile');
    $room = json_decode($roomJson, true);
    if (!is_array($room) || (string)($room['status'] ?? '') !== '200') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'room_not_found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $data = $room['data'] ?? [];
    $status = $data['realLiveStatus'] ?? '';
    $isLive = ($status === 'ON' || $status === 1 || $status === '1');
    if (!$isLive) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'not_live'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $uid = getUid();
    $cdnMapSrc = [
        'HY' => 'hycdn',
        'AL' => 'alicdn',
        'TX' => 'txcdn',
        'HW' => 'hwcdn',
        'HS' => 'hscdn',
        'WS' => 'wscdn',
    ];
    $streams = $data['stream']['baseSteamInfoList'] ?? [];
    if (!is_array($streams)) $streams = [];
    $flvMap = [];
    foreach ($streams as $s) {
        if (!is_array($s)) continue;
        $streamName = (string)($s['sStreamName'] ?? '');
        $cdnCode    = (string)($s['sCdnType']    ?? '');
        $flvUrl     = (string)($s['sFlvUrl']     ?? '');
        $flvSuffix  = (string)($s['sFlvUrlSuffix'] ?? '');
        $anti       = (string)($s['sFlvAntiCode']  ?? '');
        if ($streamName === '' || $flvUrl === '') continue;
        $cdnName = $cdnMapSrc[$cdnCode] ?? strtolower($cdnCode);
        $qs = processAnticode($anti, $uid, $streamName);
        $u = rtrim($flvUrl, '/') . '/' . $streamName . '.' . $flvSuffix . '?' . $qs;
        if (stripos($u, 'http://') === 0) $u = 'https://' . substr($u, 7);
        $flvMap[$cdnName] = $u;
    }

    if (!$flvMap) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'no_flv_stream'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    @file_put_contents($cacheFile, json_encode($flvMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

    if ($type === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($flvMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $out = $cdn && isset($flvMap[$cdn]) ? $flvMap[$cdn] : (reset($flvMap) ?: '');
    if ($out === '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'no_flv_stream'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: ' . $out, true, 302);
    exit;
} catch (\Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => 'internal_error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}