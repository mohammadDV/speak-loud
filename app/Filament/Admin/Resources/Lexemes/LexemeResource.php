<?php

namespace App\Filament\Admin\Resources\Lexemes;

use App\Domain\Dictionary\Models\Lexeme;
use App\Filament\Admin\Resources\Lexemes\Pages\CreateLexeme;
use App\Filament\Admin\Resources\Lexemes\Pages\EditLexeme;
use App\Filament\Admin\Resources\Lexemes\Pages\ListLexemes;
use App\Filament\Admin\Resources\Lexemes\RelationManagers\FormsRelationManager;
use App\Filament\Admin\Resources\Lexemes\RelationManagers\SensesRelationManager;
use App\Filament\Admin\Resources\Lexemes\Schemas\LexemeForm;
use App\Filament\Admin\Resources\Lexemes\Tables\LexemesTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LexemeResource extends Resource
{
    protected static ?string $model = Lexeme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Dictionary';

    protected static ?string $recordTitleAttribute = 'lemma';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return LexemeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LexemesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FormsRelationManager::class,
            SensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLexemes::route('/'),
            'create' => CreateLexeme::route('/create'),
            'edit' => EditLexeme::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isDictionaryStaff();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canReviewDictionary();
    }
}
