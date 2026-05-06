<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$layout = \App\Models\Layout::find(2);
$layout->cameras()->detach();

// Берём камеры с наименьшим ID для каждого канала (оригинальные)
$cameras = \App\Models\Camera::where('is_active', true)
    ->orderBy('id')
    ->get()
    ->unique('channel_number')
    ->sortBy('channel_number')
    ->values();

$position = 0;
foreach($cameras as $camera) {
    $layout->cameras()->attach($camera->id, ['position' => $position++]);
    echo "[$position] Ch{$camera->channel_number} - {$camera->name} (id:{$camera->id})\n";
}

echo "\nИтого камер: {$position}\n";
