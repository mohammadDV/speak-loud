<?php

namespace App\Domain\Learning\Enums;

enum LessonContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }
}
