<?php

namespace App\Filament\Admin\Resources\TicketCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TicketCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
            ]);
    }
}
