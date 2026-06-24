<?php

namespace App\Filament\Admin\Resources\BlogTags\Pages;

use App\Filament\Admin\Resources\BlogTags\BlogTagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogTag extends EditRecord
{
    protected static string $resource = BlogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
