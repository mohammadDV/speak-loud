<?php

namespace App\Filament\Admin\Resources\FaqCategories;

use App\Filament\Admin\Resources\FaqCategories\Pages\CreateFaqCategory;
use App\Filament\Admin\Resources\FaqCategories\Pages\EditFaqCategory;
use App\Filament\Admin\Resources\FaqCategories\Pages\ListFaqCategories;
use App\Filament\Admin\Resources\FaqCategories\Schemas\FaqCategoryForm;
use App\Filament\Admin\Resources\FaqCategories\Tables\FaqCategoriesTable;
use App\Models\FaqCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FaqCategoryResource extends Resource
{
    protected static ?string $model = FaqCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FaqCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqCategoriesTable::configure($table);
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
            'index' => ListFaqCategories::route('/'),
            'create' => CreateFaqCategory::route('/create'),
            'edit' => EditFaqCategory::route('/{record}/edit'),
        ];
    }
}
