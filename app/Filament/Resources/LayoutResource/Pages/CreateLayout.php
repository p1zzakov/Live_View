<?php

namespace App\Filament\Resources\LayoutResource\Pages;

use App\Filament\Resources\LayoutResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLayout extends CreateRecord
{
    protected static string $resource = LayoutResource::class;

    protected function afterCreate(): void
    {
        // Получаем выбранные камеры из формы
        $cameraIds = $this->data['camera_ids'] ?? [];
        
        // Привязываем с позициями
        if (!empty($cameraIds)) {
            $position = 0;
            foreach ($cameraIds as $cameraId) {
                $this->record->cameras()->attach($cameraId, [
                    'position' => $position++,
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}