<?php

namespace App\Filament\Admin\Resources\Lexemes\RelationManagers;

use App\Domain\Dictionary\Enums\FormType;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormsRelationManager extends RelationManager
{
    protected static string $relationship = 'forms';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('form')
                    ->required()
                    ->maxLength(255),
                Select::make('form_type')
                    ->options(collect(FormType::cases())->mapWithKeys(
                        fn (FormType $type) => [$type->value => $type->value]
                    )->all())
                    ->default(FormType::Inflected->value)
                    ->required(),
                Toggle::make('is_primary')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('form')
            ->columns([
                TextColumn::make('form')->searchable(),
                TextColumn::make('form_type'),
                IconColumn::make('is_primary')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $this->normalizeFormData($data))
                    ->after(fn () => $this->demoteOwner()),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $this->normalizeFormData($data))
                    ->after(fn () => $this->demoteOwner()),
                DeleteAction::make()
                    ->after(fn () => $this->demoteOwner()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn () => $this->demoteOwner()),
                ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeFormData(array $data): array
    {
        $lexeme = $this->ownerLexeme();
        $languageCode = $lexeme->language !== null ? $lexeme->language->code : 'de';
        $data['normalized_form'] = TextNormalizer::normalize((string) $data['form'], $languageCode);

        return $data;
    }

    private function demoteOwner(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $lexeme = $this->ownerLexeme()->fresh() ?? $this->ownerLexeme();
        app(ContentPublishingService::class)->demotePublishedAfterEdit($lexeme, $user, 'Lexeme form changed');
    }

    private function ownerLexeme(): Lexeme
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof Lexeme) {
            throw new \RuntimeException('Forms relation manager requires a Lexeme owner.');
        }

        $owner->loadMissing('language');

        return $owner;
    }
}
