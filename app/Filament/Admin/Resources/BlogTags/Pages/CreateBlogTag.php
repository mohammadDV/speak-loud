<?php

namespace App\Filament\Admin\Resources\BlogTags\Pages;

use App\Filament\Admin\Resources\BlogTags\BlogTagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogTag extends CreateRecord
{
    protected static string $resource = BlogTagResource::class;
}
