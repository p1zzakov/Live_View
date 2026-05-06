<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\MediaMtxConfigService::class);

// Группируем камеры по channel_number, берём только первую
$cameras = \App\Models\Camera::where('is_active', true)
    ->orderBy('channel_number')
    ->orderBy('id')
    ->get()
    ->groupBy('channel_number')
    ->map(function($group) {
        return $group->first(); // Берём первую камеру из группы
    });

echo "Найдено уникальных каналов: " . $cameras->count() . "\n\n";

foreach($cameras as $camera) {
    echo "Добавляем camera{$camera->channel_number} ({$camera->name})...\n";
    try {
        $service->addCamera($camera->channel_number, $camera->rtsp_live_url);
        echo "✓ camera{$camera->channel_number} добавлена\n";
    } catch(\Exception $e) {
        echo "✗ Ошибка: " . $e->getMessage() . "\n";
    }
}

echo "\nГотово! Перезапускаем MediaMTX...\n";
exec('sudo systemctl restart mediamtx');
sleep(2);

$status = exec('systemctl is-active mediamtx');
if ($status === 'active') {
    echo "✓ MediaMTX успешно перезапущен!\n";
} else {
    echo "✗ MediaMTX не запустился! Проверь: systemctl status mediamtx\n";
}
