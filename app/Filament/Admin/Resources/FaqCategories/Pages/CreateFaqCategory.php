<?php

namespace App\Filament\Admin\Resources\FaqCategories\Pages;

use App\Filament\Admin\Resources\FaqCategories\FaqCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaqCategory extends CreateRecord
{
    protected static string $resource = FaqCategoryResource::class;
}
