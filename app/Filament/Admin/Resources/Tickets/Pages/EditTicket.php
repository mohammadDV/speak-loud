<?php

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Actions\ReplyToTicket;
use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Repositories\Contracts\ITicketRepository;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as ViewComponent;
use Filament\Schemas\Schema;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    public string $replyBody = '';

    public bool $replyIsInternal = false;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Section::make('Conversation')
                    ->description('Public replies are sent to the user. Internal notes stay visible to staff only.')
                    ->schema([
                        ViewComponent::make('filament.admin.resources.tickets.components.messages'),
                    ]),
                Section::make('Reply')
                    ->description('Write your response below. Leave “internal note” off to send it to the user.')
                    ->schema([
                        Textarea::make('replyBody')
                            ->hiddenLabel()
                            ->placeholder('Write a reply to the user...')
                            ->rows(5)
                            ->required()
                            ->statePath('replyBody')
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Toggle::make('replyIsInternal')
                            ->label('Internal note (not visible to user)')
                            ->statePath('replyIsInternal')
                            ->dehydrated(false)
                            ->inline(false),
                    ])
                    ->footerActions([
                        Action::make('clearReply')
                            ->label('Clear')
                            ->color('gray')
                            ->action(fn () => $this->reset('replyBody', 'replyIsInternal')),
                        Action::make('sendReply')
                            ->label('Send reply')
                            ->action(fn () => $this->sendReply()),
                    ]),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getMessagesProperty()
    {
        return app(ITicketRepository::class)
            ->messagesForTicket($this->record->id, includeInternal: true);
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyBody' => 'required|string|max:5000',
        ], [
            'replyBody.required' => 'Please enter a reply before sending.',
        ]);

        app(ReplyToTicket::class)->execute(
            $this->getRecord(),
            auth()->user(),
            $this->replyBody,
            $this->replyIsInternal,
        );

        $this->reset('replyBody', 'replyIsInternal');
        $this->record->refresh();

        Notification::make()
            ->title('Reply sent')
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['status'] ?? null, ['resolved', 'closed'], true) && ! $this->record->resolved_at) {
            $data['resolved_at'] = now();
        }

        if (! in_array($data['status'] ?? null, ['resolved', 'closed'], true)) {
            $data['resolved_at'] = null;
        }

        return $data;
    }
}
