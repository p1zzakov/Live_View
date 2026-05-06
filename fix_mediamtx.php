<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$configPath = '/var/www/vms/mediamtx/mediamtx.yml';
$content = file_get_contents($configPath);

// Берём все уникальные камеры по channel_number
$cameras = \App\Models\Camera::where('is_active', true)
    ->orderBy('channel_number')
    ->get()
    ->unique('channel_number');

$added = 0;
foreach($cameras as $camera) {
    $pathName = "camera{$camera->channel_number}";
    
    // Если уже есть - пропускаем
    if (preg_match("/^\s+{$pathName}:/m", $content)) {
        echo "SKIP: {$pathName} уже есть\n";
        continue;
    }
    
    // Добавляем в конец
    $newEntry = "  {$pathName}:\n    runOnDemand: 'ffmpeg -rtsp_transport tcp -i {$camera->rtsp_live_url} -c:v libx264 -preset ultrafast -tune zerolatency -b:v 1500k -maxrate 2000k -bufsize 3000k -pix_fmt yuv420p -g 50 -c:a aac -b:a 64k -f rtsp rtsp://localhost:\$RTSP_PORT/\$MTX_PATH'\n    runOnDemandRestart: yes\n";
    
    $content = rtrim($content) . "\n" . $newEntry;
    $added++;
    echo "ADD: {$pathName} ({$camera->name}) - {$camera->rtsp_live_url}\n";
}

// Сохраняем
copy($configPath, $configPath . '.backup.' . time());
file_put_contents($configPath, $content);
echo "\nДобавлено: {$added} камер\n";
echo "Перезапускаем MediaMTX...\n";
exec('sudo systemctl restart mediamtx', $out, $code);
sleep(2);
$status = shell_exec('systemctl is-active mediamtx');
echo "Статус: " . trim($status) . "\n";
