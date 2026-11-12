<?php

namespace App\Filament\Admin\Resources\Lexemes\RelationManagers;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\GrammaticalGender;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\PartOfSpeech;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Domain\Dictionary\Support\EnumValue;
use App\Models\Language;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SensesRelationManager extends RelationManager
{
    protected static string $relationship = 'senses';

    /** @var array<string, mixed> */
    private array $pendingSenseExtras = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('part_of_speech_id')
                    ->label('Part of speech')
                    ->options(fn () => PartOfSpeech::query()->orderBy('sort_order')->pluck('display_name', 'id'))
                    ->searchable()
                    ->live(),
                Textarea::make('definition')
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('definition_language_id')
                    ->label('Definition language')
                    ->options(fn () => Language::query()->where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id'))
                    ->searchable(),
                TextInput::make('usage_label'),
                TextInput::make('domain'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Select::make('difficulty_level_id')
                    ->label('CEFR level')
                    ->options(fn () => DifficultyLevel::query()->orderBy('sort_order')->pluck('code', 'id')),
                Section::make('German noun grammar')
                    ->visible(function (callable $get): bool {
                        if ($this->ownerLexeme()->language?->code !== 'de') {
                            return false;
                        }

                        $posId = $get('part_of_speech_id');
                        if ($posId === null) {
                            return false;
                        }

                        return PartOfSpeech::query()->whereKey($posId)->value('code') === 'noun';
                    })
                    ->schema([
                        Select::make('german_article')
                            ->label('Article')
                            ->options(collect(GermanArticle::cases())->mapWithKeys(
                                fn (GermanArticle $article) => [$article->value => $article->value]
                            )->all()),
                        Select::make('german_gender')
                            ->label('Gender')
                            ->options(collect(GrammaticalGender::cases())->mapWithKeys(
                                fn (GrammaticalGender $gender) => [$gender->value => $gender->value]
                            )->all()),
                        TextInput::make('german_plural')
                            ->label('Plural form'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Repeater::make('translations')
                    ->relationship()
                    ->schema([
                        Select::make('target_language_id')
                            ->label('Language')
                            ->options(fn () => Language::query()->where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id'))
                            ->required(),
                        TextInput::make('translation_text')
                            ->required(),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => $this->forceDraftStatus($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => $this->forceDraftStatus($data))
                    ->columns(2)
                    ->columnSpanFull()
                    ->defaultItems(0),
                Repeater::make('examples')
                    ->relationship()
                    ->schema([
                        Select::make('language_id')
                            ->options(fn () => Language::query()->where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id'))
                            ->required(),
                        Textarea::make('sentence')
                            ->required()
                            ->columnSpanFull(),
                        Repeater::make('translations')
                            ->relationship()
                            ->schema([
                                Select::make('language_id')
                                    ->options(fn () => Language::query()->where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id'))
                                    ->required(),
                                Textarea::make('translation')->required(),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => $this->forceDraftStatus($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => $this->forceDraftStatus($data))
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => $this->forceDraftStatus($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => $this->forceDraftStatus($data))
                    ->defaultItems(0)
                    ->columnSpanFull(),
                Repeater::make('collocations')
                    ->relationship()
                    ->schema([
                        TextInput::make('phrase')->required(),
                        TextInput::make('pattern_type'),
                        Repeater::make('translations')
                            ->relationship()
                            ->schema([
                                Select::make('language_id')
                                    ->options(fn () => Language::query()->where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id'))
                                    ->required(),
                                TextInput::make('translation')->required(),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => $this->forceDraftStatus($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => $this->forceDraftStatus($data))
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => $this->forceDraftStatus($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => $this->forceDraftStatus($data))
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('definition')
            ->columns([
                TextColumn::make('partOfSpeech.display_name')->label('POS'),
                TextColumn::make('definition')->limit(60)->wrap(),
                TextColumn::make('status')->badge(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $this->extractSenseExtras($data))
                    ->after(function (Sense $record): void {
                        $this->syncSenseExtras($record, $this->pendingSenseExtras);
                        $this->pendingSenseExtras = [];
                        $this->demoteOwner();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Sense $record): array {
                        $detail = $record->germanNounDetail;
                        $data['german_article'] = EnumValue::string($detail?->article);
                        $data['german_gender'] = EnumValue::string($detail?->grammatical_gender);
                        $data['german_plural'] = $detail?->plural_form;
                        $data['difficulty_level_id'] = $record->primaryLevel?->difficulty_level_id;

                        return $data;
                    })
                    ->mutateFormDataUsing(fn (array $data): array => $this->extractSenseExtras($data))
                    ->after(function (Sense $record): void {
                        $this->syncSenseExtras($record, $this->pendingSenseExtras);
                        $this->pendingSenseExtras = [];
                        $this->demoteOwner();
                    }),
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
    private function forceDraftStatus(array $data): array
    {
        $data['status'] = ContentStatus::Draft->value;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractSenseExtras(array $data): array
    {
        $this->pendingSenseExtras = [
            'difficulty_level_id' => $data['difficulty_level_id'] ?? null,
            'german_article' => $data['german_article'] ?? null,
            'german_gender' => $data['german_gender'] ?? null,
            'german_plural' => $data['german_plural'] ?? null,
        ];

        unset(
            $data['difficulty_level_id'],
            $data['german_article'],
            $data['german_gender'],
            $data['german_plural'],
        );

        $data['status'] = ContentStatus::Draft->value;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSenseExtras(Sense $record, array $data): void
    {
        if (array_key_exists('difficulty_level_id', $data)) {
            if (blank($data['difficulty_level_id'])) {
                $record->levels()->where('is_primary', true)->delete();
            } else {
                $record->levels()->updateOrCreate(
                    ['is_primary' => true],
                    [
                        'difficulty_level_id' => $data['difficulty_level_id'],
                        'source' => 'editorial',
                        'assessed_at' => now(),
                    ],
                );
            }
        }

        $record->loadMissing('partOfSpeech');
        $isGermanNoun = $this->ownerLexeme()->language?->code === 'de'
            && $record->partOfSpeech?->code === 'noun';

        if ($isGermanNoun
            && (filled($data['german_article'] ?? null) || filled($data['german_plural'] ?? null) || filled($data['german_gender'] ?? null))
        ) {
            $record->germanNounDetail()->updateOrCreate(
                ['sense_id' => $record->id],
                [
                    'article' => $data['german_article'] ?? null,
                    'grammatical_gender' => $data['german_gender'] ?? null,
                    'plural_form' => $data['german_plural'] ?? null,
                ],
            );
        }
    }

    private function demoteOwner(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $lexeme = $this->ownerLexeme()->fresh() ?? $this->ownerLexeme();
        app(ContentPublishingService::class)->demotePublishedAfterEdit($lexeme, $user, 'Sense content changed');
    }

    private function ownerLexeme(): Lexeme
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof Lexeme) {
            throw new \RuntimeException('Senses relation manager requires a Lexeme owner.');
        }

        $owner->loadMissing('language');

        return $owner;
    }
}
