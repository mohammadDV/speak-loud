<?php

namespace App\Filament\Admin\Resources\Reports\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reporter_id')
                    ->relationship('reporter', 'id')
                    ->required(),
                Select::make('reported_id')
                    ->relationship('reported', 'id')
                    ->required(),
                Select::make('reason')
                    ->options([
                        'spam' => 'Spam',
                        'harassment' => 'Harassment',
                        'inappropriate_content' => 'Inappropriate content',
                        'fake_profile' => 'Fake profile',
                        'other' => 'Other',
                    ])
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'dismissed' => 'Dismissed',
                        'actioned' => 'Actioned',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('reviewed_by')
                    ->numeric(),
                DateTimePicker::make('reviewed_at'),
            ]);
    }
}
