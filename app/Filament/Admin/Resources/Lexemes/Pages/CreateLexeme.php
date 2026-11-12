<?php

namespace App\Filament\Admin\Resources\Lexemes\Pages;

use App\Domain\Dictionary\Actions\CreateLexeme as CreateLexemeAction;
use App\Domain\Dictionary\Models\Lexeme;
use App\Filament\Admin\Resources\Lexemes\LexemeResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLexeme extends CreateRecord
{
    protected static string $resource = LexemeResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Lexeme $lexeme */
        $lexeme = app(CreateLexemeAction::class)->execute([
            'language_id' => (int) $data['language_id'],
            'lemma' => (string) $data['lemma'],
            'slug' => $data['slug'] ?? null,
            'pronunciation' => $data['pronunciation'] ?? null,
            'disambiguation' => $data['disambiguation'] ?? null,
        ], $user);

        return $lexeme;
    }
}
