<?php

namespace App\Filament\Admin\Resources\TicketMessages\Pages;

use App\Filament\Admin\Resources\TicketMessages\TicketMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketMessage extends CreateRecord
{
    protected static string $resource = TicketMessageResource::class;
}
