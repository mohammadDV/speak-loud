<?php

namespace App\Filament\Admin\Resources\TicketCategories\Pages;

use App\Filament\Admin\Resources\TicketCategories\TicketCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketCategory extends CreateRecord
{
    protected static string $resource = TicketCategoryResource::class;
}
