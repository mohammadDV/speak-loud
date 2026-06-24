<?php

namespace App\Filament\Admin\Resources\TicketCategories\Pages;

use App\Filament\Admin\Resources\TicketCategories\TicketCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicketCategory extends EditRecord
{
    protected static string $resource = TicketCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
