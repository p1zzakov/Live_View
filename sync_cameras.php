<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\MediaMtxConfigService::class);
$cameras = \App\Models\Camera::where('is_active', true)
    ->orderBy('channel_number')
    ->get();

echo "Найдено камер: " . $cameras->count() . "\n\n";

foreach($cameras as $camera) {
    echo "Добавляем camera{$camera->channel_number} ({$camera->name})...\n";
    try {
        $service->addCamera($camera->channel_number, $camera->rtsp_live_url);
        echo "✓ camera{$camera->channel_number} добавлена\n";
    } catch(\Exception $e) {
        echo "✗ Ошибка: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "Готово! Перезапускаем MediaMTX...\n";
exec('sudo systemctl restart mediamtx');
sleep(2);
echo "MediaMTX перезапущен!\n";