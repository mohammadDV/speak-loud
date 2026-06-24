<?php

namespace App\Filament\Admin\Resources\Claims\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sender_id')
                    ->relationship('sender', 'id')
                    ->required(),
                Select::make('receiver_id')
                    ->relationship('receiver', 'id')
                    ->required(),
                Select::make('schedule_id')
                    ->relationship('schedule', 'title'),
                Select::make('type')
                    ->options(['schedule' => 'Schedule', 'direct' => 'Direct'])
                    ->required(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'withdrawn' => 'Withdrawn',
                        'expired' => 'Expired',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('message')
                    ->columnSpanFull(),
                DateTimePicker::make('responded_at'),
                DateTimePicker::make('expires_at'),
            ]);
    }
}
