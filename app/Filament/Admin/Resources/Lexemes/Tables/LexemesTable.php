<?php

namespace App\Filament\Admin\Resources\Lexemes\Tables;

use App\Domain\Dictionary\Enums\ContentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LexemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lemma')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('language.name_en')
                    ->label('Language')
                    ->sortable(),
                TextColumn::make('disambiguation')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ContentStatus|string $state): string => $state instanceof ContentStatus ? $state->value : $state),
                TextColumn::make('senses_count')
                    ->counts('senses')
                    ->label('Senses'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ContentStatus::cases())->mapWithKeys(
                        fn (ContentStatus $status) => [$status->value => $status->value]
                    )->all()),
                SelectFilter::make('language_id')
                    ->relationship('language', 'name_en')
                    ->label('Language'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
