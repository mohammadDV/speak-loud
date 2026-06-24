<?php

namespace App\Filament\Admin\Resources\Messages\Pages;

use App\Filament\Admin\Resources\Messages\MessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;
}
