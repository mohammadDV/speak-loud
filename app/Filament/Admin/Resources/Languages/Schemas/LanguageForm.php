<?php

namespace App\Filament\Admin\Resources\Languages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(16),
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('name_native')
                    ->required(),
                TextInput::make('script')
                    ->maxLength(32),
                Select::make('direction')
                    ->options([
                        'ltr' => 'LTR',
                        'rtl' => 'RTL',
                    ])
                    ->default('ltr')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
