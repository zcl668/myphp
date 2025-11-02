<?php
/*
食用：File Directory/cctv.php?id=cctv1&q=lg //蓝光线路[1920×1080]
      File Directory/cctv.php?id=cctv1&q=cq //超清线路[1280×720]
      File Directory/cctv.php?id=cctv1&q=gq //高清线路[960×540]
cctv1,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=11200132825562653886
cctv2,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=12030532124776958103
cctv4,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=10620168294224708952
cctv7,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=8516529981177953694
cctv9,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=7252237247689203957
cctv10,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=14589146016461298119
cctv12,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=13180385922471124325
cctv13,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=16265686808730585228
cctv17,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=4496917190172866934
cctv4k,https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=2127841942201075403
*/
error_reporting(0);
$id = isset($_GET['id']) ? $_GET['id'] : 'cctv1';
$q = isset($_GET['q']) ? $_GET['q'] : 'lg';//lg:蓝光;cq:超清;gq:高清
$n = [
    'cctv1' => '11200132825562653886', //CCTV1
    'cctv2' => '12030532124776958103', //CCTV2
    'cctv4' => '10620168294224708952', //CCTV4
    'cctv7' => '8516529981177953694', //CCTV7
    'cctv9' => '7252237247689203957', //CCTV9
    'cctv10' => '14589146016461298119', //CCTV10
    'cctv12' => '13180385922471124325', //CCTV12
    'cctv13' => '16265686808730585228', //CCTV13
    'cctv17' => '4496917190172866934', //CCTV17
    'cctv4k' => '2127841942201075403', //CCTV4K
    ];
$t = time();

$sail =  md5("articleId={$n[$id]}&scene_type=6");
$w = "&&&20000009&{$sail}&{$t}&emas.feed.article.live.detail&1.0.0&&&&&";
$k = "emasgatewayh5";
$sign = hash_hmac('sha256', $w, $k);
$url = "https://emas-api.cctvnews.cctv.com/h5/emas.feed.article.live.detail/1.0.0?articleId={$n[$id]}&scene_type=6";
$client_id = md5($t);
$h = [
    'cookieuid: '.$client_id,
    'from-client: h5',
    'referer: https://m-live.cctvnews.cctv.com/',
    'x-emas-gw-appkey: 20000009',
    'x-emas-gw-pv: 6.1',
    'x-emas-gw-sign:' .$sign,
    'x-emas-gw-t:' .$t,
    'x-req-ts:' .$t*1000,
];
$data = get($url,$h);

$data = json_decode(base64_decode(json_decode($data,1)['response']),1)['data'];

if($q == 'lg') $authUrl = $data['live_room']['liveCameraList'][0]['pullUrlList'][0]['authResultUrl'][0]['authUrl'];
if($q == 'cq') $authUrl = $data['live_room']['liveCameraList'][0]['pullUrlList'][0]['authResultUrl'][0]['demote_urls'][1]['authUrl'];
if($q == 'gq') $authUrl = $data['live_room']['liveCameraList'][0]['pullUrlList'][0]['authResultUrl'][0]['demote_urls'][0]['authUrl'];

$key = substr($data['dk'], 0, 8) . substr($t, -8);
$iv = substr($data['dk'], -8) . substr($t, 0, 8);

$live = decrypt($authUrl, $key, $iv);
header('Location: '.$live);
//print_r($live);

function get($url,$header){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function decrypt($encryptedData, $key, $iv) {
    $encryptedData = base64_decode($encryptedData);
    $decrypted = openssl_decrypt($encryptedData,'AES-128-CBC',$key,OPENSSL_RAW_DATA,$iv);
    return $decrypted;
}
?>