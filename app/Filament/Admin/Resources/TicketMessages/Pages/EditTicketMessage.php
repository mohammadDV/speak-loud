<?php

namespace App\Filament\Admin\Resources\TicketMessages\Pages;

use App\Filament\Admin\Resources\TicketMessages\TicketMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicketMessage extends EditRecord
{
    protected static string $resource = TicketMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
