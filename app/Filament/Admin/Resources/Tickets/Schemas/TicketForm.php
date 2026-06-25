<?php

namespace App\Filament\Admin\Resources\Tickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('subject')
                    ->required(),
                Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In progress',
                        'waiting_user' => 'Waiting user',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ])
                    ->default('open')
                    ->required(),
                Select::make('priority')
                    ->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'])
                    ->default('normal')
                    ->required(),
                Select::make('assigned_to')
                    ->relationship('assignee', 'email')
                    ->searchable(),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
