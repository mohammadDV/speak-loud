<?php

namespace App\Filament\Admin\Resources\Messages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('conversation_id')
                    ->relationship('conversation', 'id')
                    ->required(),
                Select::make('sender_id')
                    ->relationship('sender', 'id')
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Select::make('type')
                    ->options(['text' => 'Text', 'image' => 'Image', 'file' => 'File'])
                    ->default('text')
                    ->required(),
                TextInput::make('attachment_path'),
                Toggle::make('is_read')
                    ->required(),
                DateTimePicker::make('read_at'),
            ]);
    }
}
