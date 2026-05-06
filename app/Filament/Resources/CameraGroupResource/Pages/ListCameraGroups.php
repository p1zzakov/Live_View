<?php

namespace App\Filament\Resources\CameraGroupResource\Pages;

use App\Filament\Resources\CameraGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCameraGroups extends ListRecords
{
    protected static string $resource = CameraGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
