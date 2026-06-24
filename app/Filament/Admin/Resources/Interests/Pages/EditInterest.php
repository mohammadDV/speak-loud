<?php

namespace App\Filament\Admin\Resources\Interests\Pages;

use App\Filament\Admin\Resources\Interests\InterestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInterest extends EditRecord
{
    protected static string $resource = InterestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
