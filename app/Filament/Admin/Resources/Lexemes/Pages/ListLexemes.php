<?php

namespace App\Filament\Admin\Resources\Lexemes\Pages;

use App\Filament\Admin\Resources\Lexemes\LexemeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLexemes extends ListRecords
{
    protected static string $resource = LexemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
