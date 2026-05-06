<?php

namespace App\Filament\Resources\NvrResource\Pages;

use App\Filament\Resources\NvrResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNvr extends EditRecord
{
    protected static string $resource = NvrResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
