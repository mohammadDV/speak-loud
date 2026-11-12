<?php

namespace App\Domain\Dictionary\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::PendingReview, self::Archived], true),
            self::PendingReview => in_array($next, [self::Approved, self::Rejected, self::Draft], true),
            self::Approved => in_array($next, [self::Published, self::Draft, self::Archived], true),
            self::Published => in_array($next, [self::Draft, self::Archived], true),
            self::Rejected => in_array($next, [self::Draft, self::Archived], true),
            self::Archived => $next === self::Draft,
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }
}
