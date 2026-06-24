<?php

namespace App\Filament\Admin\Resources\Interests\Pages;

use App\Filament\Admin\Resources\Interests\InterestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInterests extends ListRecords
{
    protected static string $resource = InterestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
