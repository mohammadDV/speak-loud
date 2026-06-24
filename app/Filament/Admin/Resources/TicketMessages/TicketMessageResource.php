<?php

namespace App\Filament\Admin\Resources\TicketMessages;

use App\Filament\Admin\Resources\TicketMessages\Pages\CreateTicketMessage;
use App\Filament\Admin\Resources\TicketMessages\Pages\EditTicketMessage;
use App\Filament\Admin\Resources\TicketMessages\Pages\ListTicketMessages;
use App\Filament\Admin\Resources\TicketMessages\Schemas\TicketMessageForm;
use App\Filament\Admin\Resources\TicketMessages\Tables\TicketMessagesTable;
use App\Models\TicketMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TicketMessageResource extends Resource
{
    protected static ?string $model = TicketMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TicketMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketMessagesTable::configure($table);
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
            'index' => ListTicketMessages::route('/'),
            'create' => CreateTicketMessage::route('/create'),
            'edit' => EditTicketMessage::route('/{record}/edit'),
        ];
    }
}
