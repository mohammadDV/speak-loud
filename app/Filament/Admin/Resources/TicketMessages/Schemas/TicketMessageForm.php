<?php

namespace App\Filament\Admin\Resources\TicketMessages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TicketMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ticket_id')
                    ->relationship('ticket', 'id')
                    ->required(),
                Select::make('sender_id')
                    ->relationship('sender', 'id')
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_internal')
                    ->required(),
            ]);
    }
}
