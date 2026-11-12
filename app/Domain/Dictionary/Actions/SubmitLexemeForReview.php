<?php

namespace App\Domain\Dictionary\Actions;

use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Models\User;

class SubmitLexemeForReview
{
    public function __construct(
        private readonly ContentPublishingService $publishing,
    ) {}

    public function execute(Lexeme $lexeme, User $actor): Lexeme
    {
        return $this->publishing->submitForReview($lexeme, $actor);
    }
}
