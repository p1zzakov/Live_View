<?php

namespace App\Filament\Resources\LayoutResource\Pages;

use App\Filament\Resources\LayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLayout extends EditRecord
{
    protected static string $resource = LayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Загружаем выбранные камеры
        $data['camera_ids'] = $this->record->cameras->pluck('id')->toArray();
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Получаем новые камеры
        $cameraIds = $this->data['camera_ids'] ?? [];
        
        // Удаляем старые связи
        $this->record->cameras()->detach();
        
        // Добавляем новые с позициями
        if (!empty($cameraIds)) {
            $position = 0;
            foreach ($cameraIds as $cameraId) {
                $this->record->cameras()->attach($cameraId, [
                    'position' => $position++,
                ]);
            }
        }
    }
}