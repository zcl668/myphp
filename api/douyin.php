<?php
//本PHP首发于直播源论坛：https://bbs.livecodes.vip/，转载请注明出处，不要做那等偷摸小人！
declare(strict_types=1);

const UA_EDGE    = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0';
const UA_CHROME  = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';
const UA_IPHONE  = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1';
const TIMEOUT_S  = 12;
const SM3_IV_HEX = '7380166f4914b2b9172442d7da8a0600a96f30bc163138aae38dee4db0fb0e4e';

$GLOBALS['ABOGUS_SALT'] = 'cus';
$GLOBALS['ABOGUS_ALPHABETS'] = [
  'Dkdpgh2ZmsQB80/MfvV36XI1R45-WUAlEixNLwoqYTOPuzKFjJnry79HbGcaStCe',
  'ckdp1h4ZKsUB80/Mfvw36XIgR25+WQAlEi7NLboqYTOPuzmFjJnryx9HVGDaStCe',
];
$GLOBALS['ABOGUS_UAKEY'] = "\x00\x01\x0E";
$GLOBALS['ABOGUS_OPTIONS'] = [0, 1, 14];
$GLOBALS['ABOGUS_SORT_INDEX']  = [18,20,52,26,30,34,58,38,40,53,42,21,27,54,55,31,35,57,39,41,43,22,28,32,60,36,23,29,33,37,44,45,59,46,47,48,49,50,24,25,65,66,70,71];
$GLOBALS['ABOGUS_SORT_INDEX2'] = [18,20,26,30,34,38,40,42,21,27,31,35,39,41,43,22,28,32,36,23,29,33,37,44,45,46,47,48,49,50,24,25,52,53,54,55,57,58,59,60,65,66,70,71];
$GLOBALS['CRYPTO_BIG_ARRAY'] = [
  121,243,55,234,103,36,47,228,30,231,106,6,115,95,78,101,250,207,198,50,
  139,227,220,105,97,143,34,28,194,215,18,100,159,160,43,8,169,217,180,120,
  247,45,90,11,27,197,46,3,84,72,5,68,62,56,221,75,144,79,73,161,
  178,81,64,187,134,117,186,118,16,241,130,71,89,147,122,129,65,40,88,150,
  110,219,199,255,181,254,48,4,195,248,208,32,116,167,69,201,17,124,125,104,
  96,83,80,127,236,108,154,126,204,15,20,135,112,158,13,1,188,164,210,237,
  222,98,212,77,253,42,170,202,26,22,29,182,251,10,173,152,58,138,54,141,
  185,33,157,31,252,132,233,235,102,196,191,223,240,148,39,123,92,82,128,109,
  57,24,38,113,209,245,2,119,153,229,189,214,230,174,232,63,52,205,86,140,
  66,175,111,171,246,133,238,193,99,60,74,91,225,51,76,37,145,211,166,151,
  213,206,0,200,244,176,218,44,184,172,49,216,93,168,53,21,183,41,67,85,
  224,155,226,242,87,177,146,70,190,12,162,19,137,114,25,165,163,192,23,59,
  9,94,179,107,35,7,142,131,239,203,149,136,61,249,14,156,
];

function dy_cache_get(string $key): array {
  if (function_exists('apcu_fetch')) {
    $ok = false; $val = apcu_fetch($key, $ok);
    if ($ok) return [true, $val];
  }
  return [false, null];
}
function dy_cache_set(string $key, $val, int $ttl): void {
  if (function_exists('apcu_store')) apcu_store($key, $val, $ttl);
}

function http_get_full(string $url, array $headers = [], bool $follow = false, ?string &$rawHeadersOut = null): string {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => $follow,
    CURLOPT_CONNECTTIMEOUT => TIMEOUT_S,
    CURLOPT_TIMEOUT        => TIMEOUT_S,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_HEADER         => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_ENCODING       => ''
  ]);
  $resp = curl_exec($ch);
  if ($resp === false) { curl_close($ch); return ''; }
  $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $headersRaw = substr($resp, 0, $headerSize);
  $body       = substr($resp, $headerSize);
  curl_close($ch);
  if ($rawHeadersOut !== null) $rawHeadersOut = $headersRaw;
  return $body;
}
function http_get_body(string $url, array $headers = [], bool $follow = true): string {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => $follow,
    CURLOPT_CONNECTTIMEOUT => TIMEOUT_S,
    CURLOPT_TIMEOUT        => TIMEOUT_S,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_HEADER         => false,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_ENCODING       => ''
  ]);
  $resp = curl_exec($ch);
  curl_close($ch);
  return $resp === false ? '' : $resp;
}

function sp_to_ord_str(array $nums): string { $bytes = array_map(fn($v) => $v & 0xFF, $nums); return pack('C*', ...$bytes); }
function sp_to_ord_array(string $s): array { $o = []; for ($i=0,$n=strlen($s); $i<$n; $i++) $o[] = ord($s[$i]); return $o; }
function sp_generate_random_bytes(int $length): string {
  $out = '';
  for ($i=0; $i<$length; $i++) {
    $rd = random_int(0, 0xFFFF);
    $b0 = ((($rd & 0xFF) & 0xAA) | 0x01) & 0xFF;
    $b1 = ((($rd & 0xFF) & 0x55) | 0x02) & 0xFF;
    $hi = ($rd >> 8) & 0xFF;
    $b2 = (($hi & 0xAA) | 0x05) & 0xFF;
    $b3 = (($hi & 0x55) | 0x28) & 0xFF;
    $out .= pack('C*', $b0,$b1,$b2,$b3);
  }
  return $out;
}

function rc4_encrypt_raw(string $key, string $plaintext): string {
  $S = range(0,255); $j = 0; $kl = strlen($key);
  for ($i=0; $i<256; $i++) { $j = ($j + $S[$i] + ord($key[$i % $kl])) % 256; [$S[$i],$S[$j]] = [$S[$j],$S[$i]]; }
  $i=0; $k=0; $out='';
  for ($idx=0,$n=strlen($plaintext); $idx<$n; $idx++) {
    $i = ($i + 1) % 256; $k = ($k + $S[$i]) % 256; [$S[$i],$S[$k]] = [$S[$k],$S[$i]];
    $K = $S[($S[$i] + $S[$k]) % 256];
    $out .= chr(ord($plaintext[$idx]) ^ $K);
  }
  return $out;
}

function sm3_sign(string $str): string {
  $l = strlen($str) * 8; $k = sm3_getK($l); $bt = sm3_getB($k); $str = $str . $bt . pack('J', $l);
  $cnt = strlen($str); $blocks = intdiv($cnt, 64); $vr = hex2bin(SM3_IV_HEX);
  for ($i=0; $i<$blocks; $i++) $vr = sm3_cf($vr, substr($str, $i*64, 64));
  return bin2hex($vr);
}
function sm3_getK(int $l): int { $LEN=512; $STR=64; $v=$l%$LEN; return ($v+$STR<$LEN)?$LEN-$STR-$v-1:($LEN*2)-$STR-$v-1; }
function sm3_getB(int $k): string { $a=[128]; $a=array_merge($a,array_fill(0,intdiv($k,8),0)); return pack('C*',...$a); }
function sm3_t(int $i): int { return $i<16?0x79cc4519:0x7a879d8a; }
function sm3_lm(int $a,int $n): int { return (($a>>(32-$n)) | (($a<<$n) & 0xffffffff)); }
function sm3_ff(int $j,int $x,int $y,int $z): int { return $j<16?($x^$y^$z):(($x&$y)|($x&$z)|($y&$z)); }
function sm3_gg(int $j,int $x,int $y,int $z): int { return $j<16?($x^$y^$z):(($x&$y)|((~$x)&$z)); }
function sm3_p0(int $x): int { return $x ^ sm3_lm($x,9) ^ sm3_lm($x,17); }
function sm3_p1(int $x): int { return $x ^ sm3_lm($x,15) ^ sm3_lm($x,23); }
function sm3_cf(string $ai, string $bi): string {
  $wr = array_values(unpack('N*', $bi));
  for ($i=16; $i<68; $i++) $wr[$i] = sm3_p1($wr[$i-16]^$wr[$i-9]^sm3_lm($wr[$i-3],15)) ^ sm3_lm($wr[$i-13],7) ^ $wr[$i-6];
  $wr1=[]; for ($i=0; $i<64; $i++) $wr1[] = $wr[$i] ^ $wr[$i+4];
  [$a,$b,$c,$d,$e,$f,$g,$h] = array_values(unpack('N*', $ai));
  for ($i=0; $i<64; $i++) {
    $ss1 = sm3_lm(( (sm3_lm($a,12) + $e + (sm3_lm(sm3_t($i), $i%32)) ) & 0xffffffff ), 7);
    $ss2 = $ss1 ^ sm3_lm($a,12);
    $tt1 = (sm3_ff($i,$a,$b,$c) + $d + $ss2 + $wr1[$i]) & 0xffffffff;
    $tt2 = (sm3_gg($i,$e,$f,$g) + $h + $ss1 + $wr[$i]) & 0xffffffff;
    $d=$c; $c=sm3_lm($b,9); $b=$a; $a=$tt1; $h=$g; $g=sm3_lm($f,19); $f=$e; $e=sm3_p0($tt2);
  }
  return (pack('N*',$a,$b,$c,$d,$e,$f,$g,$h) ^ $ai);
}

function crypto_sm3_to_array($input): array {
  $data = is_string($input) ? $input : (is_array($input) ? sp_to_ord_str(array_map(fn($v)=>$v&0xFF,$input)) : '');
  $hex = sm3_sign($data); $raw = hex2bin($hex) ?: ''; return sp_to_ord_array($raw);
}
function crypto_add_salt(string $p, string $salt): string { return $p . $salt; }
function crypto_process_param($p, bool $add, string $salt) { return (is_string($p) && $add) ? crypto_add_salt($p,$salt) : $p; }
function crypto_params_to_array($p, bool $add, string $salt): array { return crypto_sm3_to_array(crypto_process_param($p,$add,$salt)); }
function crypto_transform_bytes(array $bytes, array $big): string {
  $work = $big; $s = sp_to_ord_str($bytes); $res=[]; $indexB=$work[1]; $initial=0; $e=0;
  for ($i=0,$n=strlen($s); $i<$n; $i++) {
    if ($i===0) {
      $initial=$work[$indexB]; $sum=$indexB+$initial; $work[1]=$initial; $work[$indexB]=$indexB;
      $ch=ord($s[$i]); $sum%=count($work); $f=$work[$sum]; $res[]=($ch^$f)&0xFF;
      $e=$work[($i+2)%count($work)]; $sum=($indexB+$e)%count($work); $initial=$work[$sum];
      $work[$sum]=$work[($i+2)%count($work)]; $work[($i+2)%count($work)]=$initial; $indexB=$sum; continue;
    }
    $sum=$initial+$e; $ch=ord($s[$i]); $sum%=count($work); $f=$work[$sum]; $res[]=($ch^$f)&0xFF;
    $e=$work[($i+2)%count($work)]; $sum=($indexB+$e)%count($work); $initial=$work[$sum];
    $work[$sum]=$work[($i+2)%count($work)]; $work[($i+2)%count($work)]=$initial; $indexB=$sum;
  }
  return sp_to_ord_str($res);
}
function crypto_base64_encode_bitstring(string $in, string $alphabet): string {
  $bin=''; for ($i=0,$n=strlen($in); $i<$n; $i++) $bin .= str_pad(decbin(ord($in[$i])),8,'0',STR_PAD_LEFT);
  $pad = (6 - (strlen($bin)%6)) % 6; if ($pad>0) $bin .= str_repeat('0', $pad);
  $out=''; for ($i=0,$m=strlen($bin); $i<$m; $i+=6) { $val = bindec(substr($bin,$i,6)); $out .= $alphabet[$val]; }
  $out .= str_repeat('=', intdiv($pad,2)); return $out;
}
function crypto_abogus_encode(string $bytes, string $alphabet): string {
  $n=strlen($bytes); $out='';
  for ($i=0; $i<$n; $i+=3) {
    $b0=ord($bytes[$i]); $b1=($i+1<$n)?ord($bytes[$i+1]):0; $b2=($i+2<$n)?ord($bytes[$i+2]):0;
    $val=($b0<<16)|($b1<<8)|$b2;
    $out .= $alphabet[($val&0xFC0000)>>18];
    $out .= $alphabet[($val&0x03F000)>>12];
    if ($i+1<$n) $out .= $alphabet[($val&0x000FC0)>>6];
    if ($i+2<$n) $out .= $alphabet[$val&0x00003F];
  }
  $rem=strlen($out)%4; if ($rem!==0) $out .= str_repeat('=', (4-$rem)%4);
  return $out;
}

function bfp_generate_fingerprint(string $browser): string {
  $platform = ($browser === 'Safari') ? 'MacIntel' : 'Win32';
  $innerW=random_int(1024,1920); $innerH=random_int(768,1080);
  $outerW=$innerW+random_int(24,32); $outerH=$innerH+random_int(75,90);
  $screenX=0; $screenY=[0,30][random_int(0,1)];
  $sizeW=random_int(1024,1920); $sizeH=random_int(768,1080);
  $availW=random_int(1280,1920); $availH=random_int(800,1080);
  return sprintf('%d|%d|%d|%d|%d|%d|0|0|%d|%d|%d|%d|%d|%d|24|24|%s',
    $innerW,$innerH,$outerW,$outerH,$screenX,$screenY,$sizeW,$sizeH,$availW,$availH,$innerW,$innerH,$platform);
}

function abogus_generate(string $params, string $body, string $ua, string $fp): array {
  $aid=6383; $pageId=0; $paths=['^/webcast/','^/aweme/v1/','^/aweme/v2/','/v1/message/send','^/live/','^/captcha/','^/ecom/'];
  $salt=$GLOBALS['ABOGUS_SALT']; $alph=$GLOBALS['ABOGUS_ALPHABETS']; $uaKey=$GLOBALS['ABOGUS_UAKEY']; $opts=$GLOBALS['ABOGUS_OPTIONS'];
  $big=$GLOBALS['CRYPTO_BIG_ARRAY']; $si=$GLOBALS['ABOGUS_SORT_INDEX']; $si2=$GLOBALS['ABOGUS_SORT_INDEX2'];
  $abDir=[8=>3, 15=>['aid'=>$aid,'pageId'=>$pageId,'boe'=>false,'ddrt'=>8.5,'paths'=>$paths,'track'=>['mode'=>0,'delay'=>300,'paths'=>[]],'dump'=>true,'rpU'=>''], 18=>44, 19=>[1,0,1,0,1], 66=>0,69=>0,70=>0,71=>0];
  $start=(int)floor(microtime(true)*1000);
  $a1=crypto_params_to_array(crypto_params_to_array($params,true,$salt),true,$salt);
  $a2=crypto_params_to_array($body,true,$salt);
  $rc4=rc4_encrypt_raw($uaKey,$ua);
  $uaB64=crypto_base64_encode_bitstring($rc4,$alph[1]);
  $a3=crypto_params_to_array($uaB64,false,$salt);
  $end=(int)floor(microtime(true)*1000);
  $abDir[20]=($start>>24)&0xFF; $abDir[21]=($start>>16)&0xFF; $abDir[22]=($start>>8)&0xFF; $abDir[23]=$start&0xFF;
  $abDir[24]=(int)intdiv($start,4294967296); $abDir[25]=(int)intdiv($start,1099511627776);
  $o0=$opts[0]; $o1=$opts[1]; $o2=$opts[2];
  $abDir[26]=($o0>>24)&0xFF; $abDir[27]=($o0>>16)&0xFF; $abDir[28]=($o0>>8)&0xFF; $abDir[29]=$o0&0xFF;
  $abDir[30]=(int)(($o1/256)&0xFF); $abDir[31]=(int)($o1%256); $abDir[32]=($o1>>24)&0xFF; $abDir[33]=($o1>>16)&0xFF;
  $abDir[34]=($o2>>24)&0xFF; $abDir[35]=($o2>>16)&0xFF; $abDir[36]=($o2>>8)&0xFF; $abDir[37]=$o2&0xFF;
  $abDir[38]=$a1[21]??0; $abDir[39]=$a1[22]??0; $abDir[40]=$a2[21]??0; $abDir[41]=$a2[22]??0; $abDir[42]=$a3[23]??0; $abDir[43]=$a3[24]??0;
  $abDir[44]=($end>>24)&0xFF; $abDir[45]=($end>>16)&0xFF; $abDir[46]=($end>>8)&0xFF; $abDir[47]=$end&0xFF;
  $abDir[48]=$abDir[8]; $abDir[49]=(int)intdiv($end,4294967296); $abDir[50]=(int)intdiv($end,1099511627776);
  $abDir[51]=($pageId>>24)&0xFF; $abDir[52]=($pageId>>16)&0xFF; $abDir[53]=($pageId>>8)&0xFF; $abDir[54]=$pageId&0xFF;
  $abDir[55]=$pageId; $abDir[56]=$aid; $abDir[57]=$aid&0xFF; $abDir[58]=($aid>>8)&0xFF; $abDir[59]=($aid>>16)&0xFF; $abDir[60]=($aid>>24)&0xFF;
  $abDir[64]=strlen($fp); $abDir[65]=strlen($fp);
  $sorted=[]; foreach ($si as $idx) { $v=$abDir[$idx]??0; $sorted[] = is_int($v)?$v:0; }
  $fpArr=sp_to_ord_array($fp);
  $abXor=0;
  for ($i=0; $i<count($si2)-1; $i++) {
    if ($i===0) { $v=$abDir[$si2[$i]]??0; $abXor = is_int($v)?$v:0; }
    $vn = $abDir[$si2[$i+1]] ?? 0; if (!is_int($vn)) $vn=0; $abXor ^= $vn;
  }
  $sorted = array_merge($sorted, $fpArr); $sorted[] = $abXor;
  $abBytes = sp_generate_random_bytes(3) . crypto_transform_bytes($sorted, $big);
  $abogus  = crypto_abogus_encode($abBytes, $alph[0]);
  return [$params.'&a_bogus='.$abogus, $abogus, $ua, $body];
}
function sign_detail_with_ua(string $path, string $ua = UA_EDGE): string {
  $fp = bfp_generate_fingerprint('Edge');
  [$joined] = abogus_generate($path, '', $ua, $fp);
  return $joined;
}

function generate_ms_token(int $n = 184): string {
  if ($n <= 0) $n = 184;
  $base='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_'; $len=strlen($base); $out='';
  for ($i=0; $i<$n; $i++) $out .= $base[random_int(0,$len-1)];
  return $out;
}
function hyphenate_id(string $id): string {
  $chars = preg_split('//u', $id, -1, PREG_SPLIT_NO_EMPTY);
  return implode('-', $chars ?: [$id]);
}

function dy_get_cookie(string $id): string {
  [$hit, $c] = dy_cache_get('douyinCookie');
  if ($hit) return (string)$c;
  $live = 'https://live.douyin.com/' . hyphenate_id($id);
  $raw = '';
  http_get_full($live, [
    'User-Agent: ' . UA_EDGE,
    'Upgrade-Insecure-Requests: 1',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Dest: document'
  ], false, $raw);
  $cookie = '';
  foreach (preg_split("/\r\n|\n|\r/", $raw) as $line) {
    if (stripos($line, 'Set-Cookie:') === 0 && stripos($line, 'ttwid=') !== false) {
      if (preg_match('/ttwid=[^;]+;?/i', $line, $m)) { $cookie = trim($m[0]); break; }
    }
  }
  if ($cookie !== '') dy_cache_set('douyinCookie', $cookie, 3600);
  return $cookie;
}

function pick_from_quality_map(array $map, array $prefKeys): string {
  foreach ($prefKeys as $k) if (!empty($map[$k])) return (string)$map[$k];
  foreach ($map as $v) if (!empty($v)) return (string)$v;
  return '';
}

function dy_get_live_url_from_rid(string $rid, string $stream, bool $debug = false): array {
  $cookie = dy_get_cookie($rid);
  $qs = 'aid=6383'
      . '&app_name=douyin_web'
      . '&live_id=1'
      . '&device_platform=web'
      . '&language=zh-CN'
      . '&enter_from=web_live'
      . '&cookie_enabled=true'
      . '&screen_width=1728'
      . '&screen_height=1117'
      . '&browser_language=zh-CN'
      . '&browser_platform=Win32'
      . '&browser_name=Edge'
      . '&browser_version=140.0.0.0'
      . '&web_rid=' . rawurlencode($rid)
      . '&msToken=' . rawurlencode(generate_ms_token(184));
  $signed = sign_detail_with_ua($qs, UA_EDGE);
  $url = 'https://live.douyin.com/webcast/room/web/enter/?' . $signed;
  $hdr = [
    'User-Agent: ' . UA_EDGE,
    'Cookie: ' . $cookie,
    'Accept: */*',
    'Referer: https://live.douyin.com/',
    'Origin: https://live.douyin.com',
    'Accept-Language: zh-CN,zh;q=0.9'
  ];
  $body = http_get_body($url, $hdr, true);
  if ($debug) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['cookie'=>$cookie,'enter_url'=>$url,'headers'=>$hdr,'resp_preview'=>substr($body,0,2048)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    return ['ok'=>false,'code'=>'debug','msg'=>'debug','url'=>null];
  }
  if ($body === '') return ['ok'=>false,'code'=>'invalid','msg'=>'RID无效或请求失败','url'=>null];
  $j = json_decode($body, true);
  if (!is_array($j)) return ['ok'=>false,'code'=>'invalid','msg'=>'RID无效或解析失败','url'=>null];
  $node = $j['data']['data'][0] ?? null;
  if (!is_array($node)) return ['ok'=>false,'code'=>'invalid','msg'=>'RID无效','url'=>null];
  $status = (int)($node['status'] ?? 0);
  if ($status !== 2) return ['ok'=>false,'code'=>'offline','msg'=>'该直播间未开播','url'=>null];
  $su = $node['stream_url'] ?? [];
  $real = '';
  if ($stream === 'flv') {
    if (isset($su['flv_pull_url']) && is_array($su['flv_pull_url'])) $real = pick_from_quality_map($su['flv_pull_url'], ['FULL_HD1','HD1','SD2','SD1']);
    if ($real === '' && isset($su['flv_pull_url_map']) && is_array($su['flv_pull_url_map'])) $real = pick_from_quality_map($su['flv_pull_url_map'], ['FULL_HD1','HD1','SD2','SD1']);
  } else {
    if (isset($su['hls_pull_url_map']) && is_array($su['hls_pull_url_map'])) $real = pick_from_quality_map($su['hls_pull_url_map'], ['FULL_HD1','HD1','SD2','SD1']);
    if ($real === '' && !empty($su['hls_pull_url']) && is_string($su['hls_pull_url'])) $real = (string)$su['hls_pull_url'];
  }
  if ($real === '' && isset($su['live_core_sdk_data']['pull_data']['stream_data'])) {
    $streamData = $su['live_core_sdk_data']['pull_data']['stream_data'];
    if (is_array($streamData)) {
      foreach ($streamData as $it) {
        $main = $it['data']['origin']['main'] ?? null;
        if (!is_array($main)) continue;
        if ($stream === 'flv' && !empty($main['flv'])) { $real = (string)$main['flv']; break; }
        if ($stream === 'hls' && !empty($main['hls'])) { $real = (string)$main['hls']; break; }
      }
    }
  }
  if ($real === '') return ['ok'=>false,'code'=>'error','msg'=>'未获取到播放地址','url'=>null];
  return ['ok'=>true,'code'=>'ok','msg'=>'ok','url'=>$real];
}

function extract_room_id_from_profile(array $j2): string {
  $user = $j2['user'] ?? [];
  $cands = [];
  foreach (['room_id_str','room_id','live_room_id_str','live_room_id'] as $k) if (!empty($user[$k])) $cands[] = (string)$user[$k];
  foreach ($cands as $v) if (preg_match('/^\d+$/', $v)) return $v;
  return '';
}
function extract_room_id_from_user_page(string $secId, string $cookie): string {
  $url = 'https://www.douyin.com/user/' . $secId;
  $html = http_get_body($url, [
    'User-Agent: ' . UA_EDGE,
    'Cookie: ' . $cookie,
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Referer: https://www.douyin.com/',
    'Accept-Language: zh-CN,zh;q=0.9',
  ], true);
  $patterns = [
    '/"roomId"\s*:\s*"(\d+)"/i',
    '/"room_id_str"\s*:\s*"(\d+)"/i',
    '/roomId\\":\\"(\d+)\\"/i',
    '/room_id_str\\":\\"(\d+)\\"/i'
  ];
  foreach ($patterns as $re) if (preg_match($re, $html, $m) && !empty($m[1])) return $m[1];
  return '';
}

function dy_get_live_url_from_uid(string $uid, string $stream, bool $debug = false): array {
  $u1 = 'https://www.iesdouyin.com/web/api/v2/user/info/?unique_id=' . rawurlencode($uid);
  $b1 = http_get_body($u1, [
    'User-Agent: ' . UA_EDGE,
    'Accept: */*',
    'Accept-Language: zh-CN,zh;q=0.9'
  ], true);
  if ($b1 === '') return ['ok'=>false,'code'=>'invalid','msg'=>'UID无效或请求失败','url'=>null];
  $j1 = json_decode($b1, true);
  $secId = $j1['user_info']['sec_uid'] ?? '';
  if ($secId === '') return ['ok'=>false,'code'=>'invalid','msg'=>'UID无效','url'=>null];

  $cookie = dy_get_cookie($uid);
  $roomId = extract_room_id_from_user_page($secId, $cookie);

  if ($roomId === '') {
    $qs = 'sec_user_id=' . rawurlencode($secId)
        . '&device_platform=webapp&aid=6383&channel=channel_pc_web'
        . '&publish_video_strategy_type=2&source=channel_pc_web&personal_center_strategy=1'
        . '&profile_other_record_enable=1&land_to=1&update_version_code=170400&pc_client_type=1'
        . '&pc_libra_divert=Windows&support_h265=0&support_dash=0&cpu_core_num=4'
        . '&version_code=170400&version_name=17.4.0&cookie_enabled=true'
        . '&screen_width=1728&screen_height=1084&browser_language=zh-CN&browser_platform=Win32'
        . '&browser_name=Edge&browser_version=140.0.0.0&browser_online=true'
        . '&engine_name=Blink&engine_version=140.0.0.0&os_name=Windows&os_version=10'
        . '&device_memory=8&platform=PC'
        . '&msToken=' . rawurlencode(generate_ms_token(164));
    $signed = sign_detail_with_ua($qs, UA_EDGE);
    $u2 = 'https://www.douyin.com/aweme/v1/web/user/profile/other/?' . $signed;
    $hdr2 = [
      'User-Agent: ' . UA_EDGE,
      'Cookie: ' . $cookie,
      'Referer: https://www.douyin.com/user/' . $secId,
      'Accept: */*'
    ];
    $b2 = http_get_body($u2, $hdr2, true);
    $j2 = json_decode($b2, true);
    if (is_array($j2)) $roomId = extract_room_id_from_profile($j2);
    if ($debug) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'profile_url'=>$u2,
        'profile_headers'=>$hdr2,
        'room_id_from_profile'=>$roomId,
        'profile_preview'=>substr($b2,0,1024)
      ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
      return ['ok'=>false,'code'=>'debug','msg'=>'debug','url'=>null];
    }
  }

  if ($roomId === '') return ['ok'=>false,'code'=>'offline','msg'=>'该用户未开播','url'=>null];

  $u3 = 'https://webcast.amemv.com/douyin/webcast/reflow/' . $roomId . '?sec_user_id=' . rawurlencode($secId);
  $hdr3 = [
    'User-Agent: ' . UA_IPHONE,
    'upgrade-insecure-requests: 1',
    'Accept: */*'
  ];
  $b3 = http_get_body($u3, $hdr3, true);
  if (!preg_match('/(?i)webRid.*?".*?:.*?.*?(\d+).*?"/', $b3, $m) || empty($m[1])) {
    if ($debug) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'reflow_url'=>$u3,
        'reflow_headers'=>$hdr3,
        'reflow_preview'=>substr($b3,0,1024),
        'note'=>'webRid not found'
      ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
      return ['ok'=>false,'code'=>'debug','msg'=>'debug','url'=>null];
    }
    return ['ok'=>false,'code'=>'error','msg'=>'未提取到webRid','url'=>null];
  }
  $realRid = $m[1];

  $ridRes = dy_get_live_url_from_rid($realRid, $stream, false);
  if (!$ridRes['ok']) return $ridRes;
  return ['ok'=>true,'code'=>'ok','msg'=>'ok','url'=>$ridRes['url']];
}

function dy_get_douyin_url_cached_rid(string $rid, string $stream): array {
  $key = $rid . '_' . $stream . '_riddyurl';
  [$hit,$v] = dy_cache_get($key);
  if ($hit) return ['ok'=>true,'code'=>'ok','msg'=>'ok','url'=>(string)$v];
  $res = dy_get_live_url_from_rid($rid, $stream, false);
  if ($res['ok']) dy_cache_set($key, $res['url'], 60);
  return $res;
}
function dy_get_douyin_url_cached_uid(string $uid, string $stream): array {
  $key = $uid . '_' . $stream . '_uiddyurl';
  [$hit,$v] = dy_cache_get($key);
  if ($hit) return ['ok'=>true,'code'=>'ok','msg'=>'ok','url'=>(string)$v];
  $res = dy_get_live_url_from_uid($uid, $stream, false);
  if ($res['ok']) dy_cache_set($key, $res['url'], 60);
  return $res;
}

function dy_handle_query(): void {
  $type   = strtolower(trim($_GET['type'] ?? ''));
  $stream = (($_GET['stream'] ?? 'flv') === 'hls') ? 'hls' : 'flv';
  $debug  = isset($_GET['debug']) && $_GET['debug'] == '1';

  if ($type === 'rid') {
    $rid = trim($_GET['rid'] ?? ($_GET['web_rid'] ?? ''));
    if ($rid === '') { http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo '参数错误：缺少 rid'; return; }
    if ($debug) { dy_get_live_url_from_rid($rid, $stream, true); return; }
    $res = dy_get_douyin_url_cached_rid($rid, $stream);
    if ($res['ok']) { header('Location: ' . $res['url'], true, 301); return; }
    if ($res['code'] === 'offline') { http_response_code(200); header('Content-Type: text/plain; charset=utf-8'); echo '该直播间未开播'; return; }
    if ($res['code'] === 'invalid') { http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo 'RID无效'; return; }
    http_response_code(502); header('Content-Type: text/plain; charset=utf-8'); echo '获取播放地址失败';
    return;
  }

  if ($type === 'uid') {
    $uid = trim($_GET['uid'] ?? '');
    if ($uid === '') { http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo '参数错误：缺少 uid'; return; }
    if ($debug) { dy_get_live_url_from_uid($uid, $stream, true); return; }
    $res = dy_get_douyin_url_cached_uid($uid, $stream);
    if ($res['ok']) { header('Location: ' . $res['url'], true, 301); return; }
    if ($res['code'] === 'offline') { http_response_code(200); header('Content-Type: text/plain; charset=utf-8'); echo '该用户未开播'; return; }
    if ($res['code'] === 'invalid') { http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo 'UID无效'; return; }
    http_response_code(502); header('Content-Type: text/plain; charset=utf-8'); echo '获取播放地址失败';
    return;
  }

  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo '参数错误：type 必须是 rid 或 uid';
}

dy_handle_query();
