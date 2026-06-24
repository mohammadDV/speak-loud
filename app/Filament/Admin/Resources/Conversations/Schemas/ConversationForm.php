<?php

namespace App\Filament\Admin\Resources\Conversations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(['direct' => 'Direct', 'schedule_group' => 'Schedule group'])
                    ->default('direct')
                    ->required(),
                Select::make('schedule_id')
                    ->relationship('schedule', 'title'),
                Select::make('user_a_id')
                    ->relationship('userA', 'id'),
                Select::make('user_b_id')
                    ->relationship('userB', 'id'),
                DateTimePicker::make('last_message_at'),
            ]);
    }
}
