<?php
if (isset($_GET['action']) && $_GET['action'] === 'm3u') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="live_channels.m3u"');
    
    class KzbSpider {
        private $extend;
        
        public function init($extend) {
            $this->extend = json_decode($extend, true);
        }
        
        public function liveContent() {
            $keys = ['578', '579', '580', '581', '582', '583', '584', '585', '586', '587', '588', '589', '590', '591', '592', '593', '594', '595', '596', '597', '598', '599', '600', '601', '602', '603', '604', '605', '606', '607', '608', '609', '610', '611', '612', '613', '614', '615', '616', '617', '618', '619', '620', '621', '622', '623', '624'];
            $values = [];
            
            $apiUrl = $this->extend['host'] . "/prod-api/iptv/getIptvList?liveType=0&deviceType=1";
            
            $headers = [
                'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 EdgA/136.0.0.0'
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 15
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['list'])) {
                return "#EXTM3U\n# 无法获取频道列表";
            }
            
            foreach ($data['list'] as $item) {
                $values[strval($item['id'])] = $item;
            }
            
            $tv_list = ['#EXTM3U'];
            
            foreach ($keys as $key) {
                if (!isset($values[$key])) continue;
                
                $c = $values[$key];
                $name = $c['play_source_name'];
                $group_name = (strpos($name, '卫视') !== false) ? '卫视频道' : '央视频道';
                
                $tv_list[] = '#EXTINF:-1 tvg-id="" tvg-name="" tvg-logo="https://live.fanmingming.cn/tv/' . $name . '.png" group-title="' . $group_name . '",' . $name;
                $tv_list[] = $c['play_source_url'];
            }
            
            return implode("\n", $tv_list);
        }
    }
    
    $spider = new KzbSpider();
    $spider->init('{"host":"https://jzb5kqln.huajiaedu.com"}');
    echo $spider->liveContent();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>直播频道列表</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { display: inline-block; padding: 15px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #0056b3; }
        .tips { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #007bff; }
        .channel-count { background: #28a745; color: white; padding: 5px 10px; border-radius: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📺 直播频道列表</h2>
        <p>基于官方API生成的M3U播放列表</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?action=m3u" class="btn">📥 下载M3U播放列表</a>
        </div>
        
        <div class="tips">
            <h3>📱 手机使用方法：</h3>
            <ol>
                <li>点击上方按钮下载M3U文件</li>
                <li>在手机上下载 <strong>VLC播放器</strong></li>
                <li>打开VLC → 网络 → 打开网络串流</li>
                <li>粘贴此链接：<br><code style="background: #f8f9fa; padding: 5px; border-radius: 3px; word-break: break-all;"><?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?action=m3u'; ?></code></li>
                <li>或者直接导入下载的M3U文件</li>
            </ol>
        </div>
        
        <div class="tips">
            <h3>🖥️ 电脑使用方法：</h3>
            <ul>
                <li><strong>VLC播放器</strong>：媒体 → 打开网络串流 → 粘贴链接</li>
                <li><strong>PotPlayer</strong>：打开 → 打开链接 → 粘贴链接</li>
                <li><strong>Kodi</strong>：添加IPTV源 → 输入链接</li>
            </ul>
        </div>
        
        <p><strong>支持的播放器：</strong> VLC、PotPlayer、Kodi、IPTV播放器等</p>
    </div>
</body>
</html>
