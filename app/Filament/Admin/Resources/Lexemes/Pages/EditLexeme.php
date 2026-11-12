<?php

namespace App\Filament\Admin\Resources\Lexemes\Pages;

use App\Domain\Dictionary\Actions\ApproveLexeme;
use App\Domain\Dictionary\Actions\ArchiveLexeme;
use App\Domain\Dictionary\Actions\PublishLexeme;
use App\Domain\Dictionary\Actions\RejectLexeme;
use App\Domain\Dictionary\Actions\SubmitLexemeForReview;
use App\Domain\Dictionary\Actions\UpdateLexeme;
use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Filament\Admin\Resources\Lexemes\LexemeResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditLexeme extends EditRecord
{
    protected static string $resource = LexemeResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Lexeme $record */
        return app(UpdateLexeme::class)->execute($record, [
            'lemma' => $data['lemma'] ?? $record->lemma,
            'slug' => $data['slug'] ?? $record->slug,
            'pronunciation' => $data['pronunciation'] ?? $record->pronunciation,
            'disambiguation' => $data['disambiguation'] ?? $record->disambiguation,
        ], $user);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitReview')
                ->label('Submit for review')
                ->visible(fn (): bool => $this->actor()?->isDictionaryStaff()
                    && $this->lexeme()->status === ContentStatus::Draft)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runWorkflow(fn (Lexeme $lexeme, User $user) => app(SubmitLexemeForReview::class)->execute($lexeme, $user));
                }),
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn (): bool => $this->actor()?->canReviewDictionary()
                    && $this->lexeme()->status === ContentStatus::PendingReview)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runWorkflow(fn (Lexeme $lexeme, User $user) => app(ApproveLexeme::class)->execute($lexeme, $user));
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->visible(fn (): bool => $this->actor()?->canReviewDictionary()
                    && $this->lexeme()->status === ContentStatus::PendingReview)
                ->form([
                    Textarea::make('comments')->label('Comments')->required(),
                ])
                ->action(function (array $data): void {
                    $this->runWorkflow(fn (Lexeme $lexeme, User $user) => app(RejectLexeme::class)->execute(
                        $lexeme,
                        $user,
                        $data['comments'] ?? null,
                    ));
                }),
            Action::make('publish')
                ->label('Publish')
                ->color('success')
                ->visible(fn (): bool => $this->actor()?->canReviewDictionary()
                    && $this->lexeme()->status === ContentStatus::Approved)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runWorkflow(fn (Lexeme $lexeme, User $user) => app(PublishLexeme::class)->execute($lexeme, $user));
                }),
            Action::make('returnToDraft')
                ->label('Return to draft')
                ->color('warning')
                ->visible(fn (): bool => $this->actor()?->isDictionaryStaff()
                    && in_array($this->lexeme()->status, [
                        ContentStatus::PendingReview,
                        ContentStatus::Approved,
                        ContentStatus::Rejected,
                        ContentStatus::Published,
                    ], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runWorkflow(fn (Lexeme $lexeme, User $user) => app(ContentPublishingService::class)->returnToDraft($lexeme, $user));
                }),
            Action::make('archive')
                ->label('Archive')
                ->color('gray')
                ->visible(fn (): bool => $this->actor()?->canReviewDictionary()
                    && in_array($this->lexeme()->status, [ContentStatus::Published, ContentStatus::Approved], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runWorkflow(fn (Lexeme $lexeme, User $user) => app(ArchiveLexeme::class)->execute($lexeme, $user));
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  callable(Lexeme, User): Lexeme  $callback
     */
    private function runWorkflow(callable $callback): void
    {
        $user = $this->actor();

        if ($user === null) {
            return;
        }

        try {
            $fresh = $callback($this->lexeme(), $user);
            $this->record = $fresh;
            $this->refreshFormData(['status', 'lemma', 'slug', 'pronunciation', 'disambiguation']);
            Notification::make()->title('Workflow updated')->success()->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Workflow failed')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->danger()
                ->send();
        }
    }

    private function actor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function lexeme(): Lexeme
    {
        /** @var Lexeme $record */
        $record = $this->getRecord();

        return $record;
    }
}
