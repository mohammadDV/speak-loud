<?php

namespace App\Filament\Admin\Resources\Claims;

use App\Filament\Admin\Resources\Claims\Pages\CreateClaim;
use App\Filament\Admin\Resources\Claims\Pages\EditClaim;
use App\Filament\Admin\Resources\Claims\Pages\ListClaims;
use App\Filament\Admin\Resources\Claims\Schemas\ClaimForm;
use App\Filament\Admin\Resources\Claims\Tables\ClaimsTable;
use App\Models\Claim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClaimResource extends Resource
{
    protected static ?string $model = Claim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimsTable::configure($table);
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
            'index' => ListClaims::route('/'),
            'create' => CreateClaim::route('/create'),
            'edit' => EditClaim::route('/{record}/edit'),
        ];
    }
}
