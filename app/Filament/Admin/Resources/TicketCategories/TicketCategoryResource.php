<?php

namespace App\Filament\Admin\Resources\TicketCategories;

use App\Filament\Admin\Resources\TicketCategories\Pages\CreateTicketCategory;
use App\Filament\Admin\Resources\TicketCategories\Pages\EditTicketCategory;
use App\Filament\Admin\Resources\TicketCategories\Pages\ListTicketCategories;
use App\Filament\Admin\Resources\TicketCategories\Schemas\TicketCategoryForm;
use App\Filament\Admin\Resources\TicketCategories\Tables\TicketCategoriesTable;
use App\Models\TicketCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TicketCategoryResource extends Resource
{
    protected static ?string $model = TicketCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TicketCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketCategoriesTable::configure($table);
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
            'index' => ListTicketCategories::route('/'),
            'create' => CreateTicketCategory::route('/create'),
            'edit' => EditTicketCategory::route('/{record}/edit'),
        ];
    }
}
