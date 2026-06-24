<?php

namespace App\Filament\Admin\Resources\Interests;

use App\Filament\Admin\Resources\Interests\Pages\CreateInterest;
use App\Filament\Admin\Resources\Interests\Pages\EditInterest;
use App\Filament\Admin\Resources\Interests\Pages\ListInterests;
use App\Filament\Admin\Resources\Interests\Schemas\InterestForm;
use App\Filament\Admin\Resources\Interests\Tables\InterestsTable;
use App\Models\Interest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InterestResource extends Resource
{
    protected static ?string $model = Interest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InterestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterests::route('/'),
            'create' => CreateInterest::route('/create'),
            'edit' => EditInterest::route('/{record}/edit'),
        ];
    }
}
