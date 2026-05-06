<?php

namespace App\Filament\Resources\NvrResource\Pages;

use App\Filament\Resources\NvrResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNvrs extends ListRecords
{
    protected static string $resource = NvrResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
