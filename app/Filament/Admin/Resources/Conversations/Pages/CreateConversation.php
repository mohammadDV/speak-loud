<?php

namespace App\Filament\Admin\Resources\Conversations\Pages;

use App\Filament\Admin\Resources\Conversations\ConversationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConversation extends CreateRecord
{
    protected static string $resource = ConversationResource::class;
}
