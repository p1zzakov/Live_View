<?php

namespace App\Filament\Resources\LayoutResource\Pages;

use App\Filament\Resources\LayoutResource;
use Filament\Resources\Pages\Page;

class ManageLayoutPositions extends Page
{
    protected static string $resource = LayoutResource::class;

    protected static string $view = 'filament.resources.layout-resource.pages.manage-layout-positions';
}
