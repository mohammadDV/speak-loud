<?php

namespace App\Filament\Admin\Resources\Schedules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                TextInput::make('title'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('type')
                    ->options(['recurring' => 'Recurring', 'one_time' => 'One time'])
                    ->required(),
                Select::make('language_id')
                    ->relationship('language', 'id')
                    ->required(),
                TextInput::make('max_participants')
                    ->required()
                    ->numeric()
                    ->default(1),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'cancelled' => 'Cancelled'])
                    ->default('active')
                    ->required(),
            ]);
    }
}
