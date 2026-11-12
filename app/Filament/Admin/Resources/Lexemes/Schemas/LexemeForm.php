<?php

namespace App\Filament\Admin\Resources\Lexemes\Schemas;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Models\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LexemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('language_id')
                    ->label('Language')
                    ->options(fn () => Language::query()->where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id'))
                    ->searchable()
                    ->required()
                    ->disabledOn('edit'),
                TextInput::make('lemma')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->maxLength(255),
                TextInput::make('pronunciation')
                    ->maxLength(255),
                TextInput::make('disambiguation')
                    ->maxLength(255)
                    ->helperText('Optional label when multiple lexemes share the same spelling.'),
                Select::make('status')
                    ->options(collect(ContentStatus::cases())->mapWithKeys(
                        fn (ContentStatus $status) => [$status->value => str_replace('_', ' ', ucfirst($status->value))]
                    )->all())
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }
}
