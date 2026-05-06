<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$configPath = '/var/www/vms/mediamtx/mediamtx.yml';
$content = file_get_contents($configPath);
$headerEnd = strpos($content, 'paths:');
$header = substr($content, 0, $headerEnd) . "paths:\n";

$cameras = \App\Models\Camera::where('is_active', true)
    ->orderBy('nvr_id')
    ->orderBy('channel_number')
    ->get()
    ->unique(fn($c) => $c->nvr_id . '_' . $c->channel_number);

$paths = '';
foreach($cameras as $camera) {
    // Уникальный путь: nvr1ch1, nvr2ch5, nvr3ch1 - никаких конфликтов!
    $pathName = "nvr{$camera->nvr_id}ch{$camera->channel_number}";
    $rtspUrl = preg_replace('/subtype=\d/', 'subtype=1', $camera->rtsp_live_url);

    $ffmpegCmd = "ffmpeg -rtsp_transport tcp -i {$rtspUrl} -c:v copy -an -f rtsp rtsp://localhost:\$RTSP_PORT/\$MTX_PATH";

    $paths .= "  {$pathName}:\n";
    $paths .= "    runOnDemand: '{$ffmpegCmd}'\n";
    $paths .= "    runOnDemandRestart: yes\n";

    echo "Added: {$pathName} -> {$rtspUrl}\n";
}

copy($configPath, $configPath . '.backup.' . time());
file_put_contents($configPath, $header . $paths);

exec('sudo systemctl restart mediamtx', $out, $code);
sleep(2);
$status = trim(shell_exec('systemctl is-active mediamtx'));
echo "\nМедиаМТХ: {$status}\n";
echo "Готово! Теперь формат: nvr{id}ch{channel}\n";