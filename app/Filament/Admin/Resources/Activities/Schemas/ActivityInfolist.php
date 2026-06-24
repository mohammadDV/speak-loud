<?php

namespace App\Filament\Admin\Resources\Activities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Activity')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')->dateTime()->label('When'),
                    TextEntry::make('log_name')->label('Log')->badge(),
                    TextEntry::make('event')->badge(),
                    TextEntry::make('description'),
                    TextEntry::make('subject_type')
                        ->label('Subject type')
                        ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                    TextEntry::make('subject_id')->label('Subject ID'),
                    TextEntry::make('causer.email')->label('Caused by')->placeholder('system'),
                    TextEntry::make('batch_uuid')->label('Batch UUID')->placeholder('—'),
                ]),
            Section::make('Changes')
                ->schema([
                    TextEntry::make('properties')
                        ->label('Properties')
                        ->formatStateUsing(fn ($state) => json_encode(
                            $state instanceof Collection ? $state->toArray() : $state,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                        ))
                        ->html()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
