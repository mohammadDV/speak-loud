<?php

namespace Database\Seeders;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\FormType;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\GrammaticalGender;
use App\Domain\Dictionary\Enums\PhraseType;
use App\Domain\Dictionary\Enums\TranslationType;
use App\Domain\Dictionary\Models\Collocation;
use App\Domain\Dictionary\Models\CollocationTranslation;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\Example;
use App\Domain\Dictionary\Models\ExampleTranslation;
use App\Domain\Dictionary\Models\GermanNounDetail;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\LexemeForm;
use App\Domain\Dictionary\Models\PartOfSpeech;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseLevel;
use App\Domain\Dictionary\Models\SenseTranslation;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DictionaryFixtureSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DictionaryReferenceSeeder::class);

        $de = Language::query()->where('code', 'de')->firstOrFail();
        $en = Language::query()->where('code', 'en')->firstOrFail();
        $fa = Language::query()->where('code', 'fa')->firstOrFail();

        $noun = PartOfSpeech::query()->where('code', 'noun')->firstOrFail();
        $verb = PartOfSpeech::query()->where('code', 'verb')->firstOrFail();
        $adj = PartOfSpeech::query()->where('code', 'adjective')->firstOrFail();

        $a1 = DifficultyLevel::query()->where('code', 'A1')->firstOrFail();
        $a2 = DifficultyLevel::query()->where('code', 'A2')->firstOrFail();
        $b1 = DifficultyLevel::query()->where('code', 'B1')->firstOrFail();

        DB::transaction(function () use ($de, $en, $fa, $noun, $verb, $adj, $a1, $a2, $b1): void {
            $this->seedBank($de, $en, $fa, $noun, $a1, $a2);
            $this->seedHaus($de, $en, $fa, $noun, $a1);
            $this->seedBuch($de, $en, $fa, $noun, $a1);
            $this->seedKind($de, $en, $fa, $noun, $a1);
            $this->seedMann($de, $en, $fa, $noun, $a1);
            $this->seedFrau($de, $en, $fa, $noun, $a1);
            $this->seedTisch($de, $en, $fa, $noun, $a1);
            $this->seedWasser($de, $en, $fa, $noun, $a1);
            $this->seedGehen($de, $en, $fa, $verb, $a1);
            $this->seedSprechen($de, $en, $fa, $verb, $a1);
            $this->seedEssen($de, $en, $fa, $verb, $a1);
            $this->seedLernen($de, $en, $fa, $verb, $a1);
            $this->seedGut($de, $en, $fa, $adj, $a1);
            $this->seedSchon($de, $en, $fa, $adj, $a1);
            $this->seedGross($de, $en, $fa, $adj, $a1);
            $this->seedEntscheidung($de, $en, $fa, $noun, $b1);
            $this->seedZeit($de, $en, $fa, $noun, $a1);
            $this->seedArbeit($de, $en, $fa, $noun, $a2);
            $this->seedFreund($de, $en, $fa, $noun, $a1);
            $this->seedStadt($de, $en, $fa, $noun, $a1);
            $this->seedLauf($de, $en, $fa, $noun, $verb, $a2, $b1);
        });
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function createLexeme(Language $language, string $lemma, array $attrs = []): Lexeme
    {
        return Lexeme::query()->updateOrCreate(
            [
                'language_id' => $language->id,
                'lemma' => $lemma,
                'disambiguation' => $attrs['disambiguation'] ?? null,
            ],
            [
                'normalized_lemma' => TextNormalizer::normalize($lemma, $language->code),
                'slug' => $attrs['slug'] ?? Str::slug($lemma).'-'.$language->code,
                'status' => ContentStatus::Published,
                ...collect($attrs)->except(['disambiguation', 'slug'])->all(),
            ],
        );
    }

    private function addForm(Lexeme $lexeme, string $form, FormType $type = FormType::Inflected): LexemeForm
    {
        return LexemeForm::query()->updateOrCreate(
            [
                'lexeme_id' => $lexeme->id,
                'form' => $form,
            ],
            [
                'normalized_form' => TextNormalizer::normalize($form, $lexeme->language->code ?? 'de'),
                'form_type' => $type,
                'is_primary' => false,
            ],
        );
    }

    /**
     * @param  array{en?: string, fa?: string}  $translations
     * @param  array{sentence: string, en?: string, fa?: string}|null  $example
     * @param  list<array{phrase: string, en?: string, fa?: string, type?: PhraseType}>  $collocations
     */
    private function addSense(
        Lexeme $lexeme,
        PartOfSpeech $pos,
        string $definition,
        Language $definitionLanguage,
        DifficultyLevel $level,
        Language $en,
        Language $fa,
        array $translations,
        ?array $example = null,
        array $collocations = [],
        ?array $germanNoun = null,
        int $sortOrder = 0,
    ): Sense {
        $sense = Sense::query()->updateOrCreate(
            [
                'lexeme_id' => $lexeme->id,
                'definition' => $definition,
            ],
            [
                'part_of_speech_id' => $pos->id,
                'definition_language_id' => $definitionLanguage->id,
                'sort_order' => $sortOrder,
                'status' => ContentStatus::Published,
            ],
        );

        SenseLevel::query()->updateOrCreate(
            ['sense_id' => $sense->id, 'is_primary' => true],
            [
                'difficulty_level_id' => $level->id,
                'source' => 'fixture',
                'assessed_at' => now(),
            ],
        );

        if (isset($translations['en'])) {
            SenseTranslation::query()->updateOrCreate(
                [
                    'sense_id' => $sense->id,
                    'target_language_id' => $en->id,
                    'translation_text' => $translations['en'],
                ],
                [
                    'translation_type' => TranslationType::Equivalent,
                    'status' => ContentStatus::Published,
                    'sort_order' => 0,
                ],
            );
        }

        if (isset($translations['fa'])) {
            SenseTranslation::query()->updateOrCreate(
                [
                    'sense_id' => $sense->id,
                    'target_language_id' => $fa->id,
                    'translation_text' => $translations['fa'],
                ],
                [
                    'translation_type' => TranslationType::Equivalent,
                    'status' => ContentStatus::Published,
                    'sort_order' => 0,
                ],
            );
        }

        if ($germanNoun !== null) {
            GermanNounDetail::query()->updateOrCreate(
                ['sense_id' => $sense->id],
                [
                    'grammatical_gender' => $germanNoun['gender'],
                    'article' => $germanNoun['article'],
                    'plural_form' => $germanNoun['plural'],
                ],
            );
        }

        if ($example !== null) {
            $ex = Example::query()->updateOrCreate(
                [
                    'sense_id' => $sense->id,
                    'sentence' => $example['sentence'],
                ],
                [
                    'language_id' => $lexeme->language_id,
                    'status' => ContentStatus::Published,
                    'sort_order' => 0,
                ],
            );

            if (isset($example['en'])) {
                ExampleTranslation::query()->updateOrCreate(
                    ['example_id' => $ex->id, 'language_id' => $en->id],
                    ['translation' => $example['en'], 'status' => ContentStatus::Published],
                );
            }

            if (isset($example['fa'])) {
                ExampleTranslation::query()->updateOrCreate(
                    ['example_id' => $ex->id, 'language_id' => $fa->id],
                    ['translation' => $example['fa'], 'status' => ContentStatus::Published],
                );
            }
        }

        foreach ($collocations as $i => $col) {
            $collocation = Collocation::query()->updateOrCreate(
                [
                    'sense_id' => $sense->id,
                    'phrase' => $col['phrase'],
                ],
                [
                    'phrase_type' => $col['type'] ?? PhraseType::Collocation,
                    'pattern_type' => $col['pattern'] ?? null,
                    'status' => ContentStatus::Published,
                    'sort_order' => $i,
                ],
            );

            if (isset($col['en'])) {
                CollocationTranslation::query()->updateOrCreate(
                    ['collocation_id' => $collocation->id, 'language_id' => $en->id],
                    ['translation' => $col['en'], 'status' => ContentStatus::Published],
                );
            }

            if (isset($col['fa'])) {
                CollocationTranslation::query()->updateOrCreate(
                    ['collocation_id' => $collocation->id, 'language_id' => $fa->id],
                    ['translation' => $col['fa'], 'status' => ContentStatus::Published],
                );
            }
        }

        return $sense;
    }

    private function seedBank(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1, DifficultyLevel $a2): void
    {
        $lexeme = $this->createLexeme($de, 'Bank', ['slug' => 'bank-de']);
        $this->addForm($lexeme, 'Banken');

        $this->addSense(
            $lexeme,
            $noun,
            'A financial institution that accepts deposits and makes loans.',
            $en,
            $a1,
            $en,
            $fa,
            ['en' => 'bank', 'fa' => 'بانک'],
            [
                'sentence' => 'Ich gehe zur Bank.',
                'en' => 'I am going to the bank.',
                'fa' => 'من به بانک می‌روم.',
            ],
            [
                ['phrase' => 'ein Konto eröffnen', 'en' => 'open an account', 'fa' => 'حساب باز کردن', 'pattern' => 'verb+noun'],
                ['phrase' => 'Geld abheben', 'en' => 'withdraw money', 'fa' => 'پول برداشتن', 'pattern' => 'verb+noun'],
                ['phrase' => 'Geld einzahlen', 'en' => 'deposit money', 'fa' => 'پول واریز کردن', 'pattern' => 'verb+noun'],
            ],
            [
                'gender' => GrammaticalGender::Feminine,
                'article' => GermanArticle::Die,
                'plural' => 'Banken',
            ],
            0,
        );

        $this->addSense(
            $lexeme,
            $noun,
            'A long seat for several people, typically outdoors.',
            $en,
            $a2,
            $en,
            $fa,
            ['en' => 'bench', 'fa' => 'نیمکت'],
            [
                'sentence' => 'Wir sitzen auf der Bank.',
                'en' => 'We are sitting on the bench.',
                'fa' => 'ما روی نیمکت نشسته‌ایم.',
            ],
            [],
            [
                'gender' => GrammaticalGender::Feminine,
                'article' => GermanArticle::Die,
                'plural' => 'Bänke',
            ],
            1,
        );
    }

    private function seedHaus(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Haus', ['slug' => 'haus-de']);
        $this->addForm($lexeme, 'Häuser');
        $this->addSense($lexeme, $noun, 'A building for human habitation.', $en, $a1, $en, $fa, ['en' => 'house', 'fa' => 'خانه'], [
            'sentence' => 'Das Haus ist groß.',
            'en' => 'The house is big.',
            'fa' => 'خانه بزرگ است.',
        ], [], [
            'gender' => GrammaticalGender::Neuter,
            'article' => GermanArticle::Das,
            'plural' => 'Häuser',
        ]);
    }

    private function seedBuch(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Buch', ['slug' => 'buch-de']);
        $this->addForm($lexeme, 'Bücher');
        $this->addSense($lexeme, $noun, 'A written or printed work consisting of pages.', $en, $a1, $en, $fa, ['en' => 'book', 'fa' => 'کتاب'], [
            'sentence' => 'Ich lese ein Buch.',
            'en' => 'I am reading a book.',
            'fa' => 'من یک کتاب می‌خوانم.',
        ], [], [
            'gender' => GrammaticalGender::Neuter,
            'article' => GermanArticle::Das,
            'plural' => 'Bücher',
        ]);
    }

    private function seedKind(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Kind', ['slug' => 'kind-de']);
        $this->addForm($lexeme, 'Kinder');
        $this->addSense($lexeme, $noun, 'A young human being.', $en, $a1, $en, $fa, ['en' => 'child', 'fa' => 'کودک'], null, [], [
            'gender' => GrammaticalGender::Neuter,
            'article' => GermanArticle::Das,
            'plural' => 'Kinder',
        ]);
    }

    private function seedMann(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Mann', ['slug' => 'mann-de']);
        $this->addForm($lexeme, 'Männer');
        $this->addSense($lexeme, $noun, 'An adult male human.', $en, $a1, $en, $fa, ['en' => 'man', 'fa' => 'مرد'], null, [], [
            'gender' => GrammaticalGender::Masculine,
            'article' => GermanArticle::Der,
            'plural' => 'Männer',
        ]);
    }

    private function seedFrau(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Frau', ['slug' => 'frau-de']);
        $this->addForm($lexeme, 'Frauen');
        $this->addSense($lexeme, $noun, 'An adult female human.', $en, $a1, $en, $fa, ['en' => 'woman', 'fa' => 'زن'], null, [], [
            'gender' => GrammaticalGender::Feminine,
            'article' => GermanArticle::Die,
            'plural' => 'Frauen',
        ]);
    }

    private function seedTisch(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Tisch', ['slug' => 'tisch-de']);
        $this->addForm($lexeme, 'Tische');
        $this->addSense($lexeme, $noun, 'A piece of furniture with a flat top.', $en, $a1, $en, $fa, ['en' => 'table', 'fa' => 'میز'], null, [], [
            'gender' => GrammaticalGender::Masculine,
            'article' => GermanArticle::Der,
            'plural' => 'Tische',
        ]);
    }

    private function seedWasser(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Wasser', ['slug' => 'wasser-de']);
        $this->addSense($lexeme, $noun, 'A clear liquid essential for life.', $en, $a1, $en, $fa, ['en' => 'water', 'fa' => 'آب'], null, [], [
            'gender' => GrammaticalGender::Neuter,
            'article' => GermanArticle::Das,
            'plural' => 'Wasser',
        ]);
    }

    private function seedGehen(Language $de, Language $en, Language $fa, PartOfSpeech $verb, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'gehen', ['slug' => 'gehen-de']);
        foreach (['geht', 'ging', 'gegangen'] as $form) {
            $this->addForm($lexeme, $form);
        }
        $this->addSense($lexeme, $verb, 'To move on foot; to go.', $en, $a1, $en, $fa, ['en' => 'to go', 'fa' => 'رفتن'], [
            'sentence' => 'Wir gehen nach Hause.',
            'en' => 'We are going home.',
            'fa' => 'ما به خانه می‌رویم.',
        ]);
    }

    private function seedSprechen(Language $de, Language $en, Language $fa, PartOfSpeech $verb, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'sprechen', ['slug' => 'sprechen-de']);
        foreach (['spricht', 'sprach', 'gesprochen'] as $form) {
            $this->addForm($lexeme, $form);
        }
        $this->addSense($lexeme, $verb, 'To talk; to speak a language.', $en, $a1, $en, $fa, ['en' => 'to speak', 'fa' => 'صحبت کردن']);
    }

    private function seedEssen(Language $de, Language $en, Language $fa, PartOfSpeech $verb, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'essen', ['slug' => 'essen-de']);
        foreach (['isst', 'aß', 'gegessen'] as $form) {
            $this->addForm($lexeme, $form);
        }
        $this->addSense($lexeme, $verb, 'To consume food.', $en, $a1, $en, $fa, ['en' => 'to eat', 'fa' => 'خوردن']);
    }

    private function seedLernen(Language $de, Language $en, Language $fa, PartOfSpeech $verb, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'lernen', ['slug' => 'lernen-de']);
        foreach (['lernt', 'lernte', 'gelernt'] as $form) {
            $this->addForm($lexeme, $form);
        }
        $this->addSense($lexeme, $verb, 'To acquire knowledge or skill.', $en, $a1, $en, $fa, ['en' => 'to learn', 'fa' => 'یاد گرفتن']);
    }

    private function seedGut(Language $de, Language $en, Language $fa, PartOfSpeech $adj, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'gut', ['slug' => 'gut-de']);
        $this->addSense($lexeme, $adj, 'Of high quality; satisfactory.', $en, $a1, $en, $fa, ['en' => 'good', 'fa' => 'خوب']);
    }

    private function seedSchon(Language $de, Language $en, Language $fa, PartOfSpeech $adj, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'schön', ['slug' => 'schoen-de']);
        $this->addSense($lexeme, $adj, 'Pleasing to the senses; beautiful.', $en, $a1, $en, $fa, ['en' => 'beautiful', 'fa' => 'زیبا']);
    }

    private function seedGross(Language $de, Language $en, Language $fa, PartOfSpeech $adj, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'groß', ['slug' => 'gross-de']);
        $this->addSense($lexeme, $adj, 'Of considerable size or extent.', $en, $a1, $en, $fa, ['en' => 'big', 'fa' => 'بزرگ']);
    }

    private function seedEntscheidung(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $b1): void
    {
        $lexeme = $this->createLexeme($de, 'Entscheidung', ['slug' => 'entscheidung-de']);
        $this->addForm($lexeme, 'Entscheidungen');
        $this->addSense($lexeme, $noun, 'A conclusion or resolution reached after consideration.', $en, $b1, $en, $fa, ['en' => 'decision', 'fa' => 'تصمیم'], [
            'sentence' => 'Sie trifft eine Entscheidung.',
            'en' => 'She is making a decision.',
            'fa' => 'او یک تصمیم می‌گیرد.',
        ], [
            ['phrase' => 'eine Entscheidung treffen', 'en' => 'make a decision', 'fa' => 'تصمیم گرفتن', 'pattern' => 'verb+noun'],
        ], [
            'gender' => GrammaticalGender::Feminine,
            'article' => GermanArticle::Die,
            'plural' => 'Entscheidungen',
        ]);
    }

    private function seedZeit(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Zeit', ['slug' => 'zeit-de']);
        $this->addForm($lexeme, 'Zeiten');
        $this->addSense($lexeme, $noun, 'The indefinite continued progress of existence.', $en, $a1, $en, $fa, ['en' => 'time', 'fa' => 'زمان'], null, [], [
            'gender' => GrammaticalGender::Feminine,
            'article' => GermanArticle::Die,
            'plural' => 'Zeiten',
        ]);
    }

    private function seedArbeit(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a2): void
    {
        $lexeme = $this->createLexeme($de, 'Arbeit', ['slug' => 'arbeit-de']);
        $this->addForm($lexeme, 'Arbeiten');
        $this->addSense($lexeme, $noun, 'Activity involving mental or physical effort; a job.', $en, $a2, $en, $fa, ['en' => 'work', 'fa' => 'کار'], null, [], [
            'gender' => GrammaticalGender::Feminine,
            'article' => GermanArticle::Die,
            'plural' => 'Arbeiten',
        ]);
    }

    private function seedFreund(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Freund', ['slug' => 'freund-de']);
        $this->addForm($lexeme, 'Freunde');
        $this->addSense($lexeme, $noun, 'A person with whom one has a bond of mutual affection.', $en, $a1, $en, $fa, ['en' => 'friend', 'fa' => 'دوست'], null, [], [
            'gender' => GrammaticalGender::Masculine,
            'article' => GermanArticle::Der,
            'plural' => 'Freunde',
        ]);
    }

    private function seedStadt(Language $de, Language $en, Language $fa, PartOfSpeech $noun, DifficultyLevel $a1): void
    {
        $lexeme = $this->createLexeme($de, 'Stadt', ['slug' => 'stadt-de']);
        $this->addForm($lexeme, 'Städte');
        $this->addSense($lexeme, $noun, 'A large human settlement.', $en, $a1, $en, $fa, ['en' => 'city', 'fa' => 'شهر'], null, [], [
            'gender' => GrammaticalGender::Feminine,
            'article' => GermanArticle::Die,
            'plural' => 'Städte',
        ]);
    }

    private function seedLauf(Language $de, Language $en, Language $fa, PartOfSpeech $noun, PartOfSpeech $verb, DifficultyLevel $a2, DifficultyLevel $b1): void
    {
        // Homograph-style: same spelling as noun and verb uses — separate senses on one lexeme for "Lauf" noun; verb "laufen" separate.
        $nounLexeme = $this->createLexeme($de, 'Lauf', ['slug' => 'lauf-noun-de', 'disambiguation' => 'noun']);
        $this->addForm($nounLexeme, 'Läufe');
        $this->addSense($nounLexeme, $noun, 'A run or course; the act of running.', $en, $a2, $en, $fa, ['en' => 'run', 'fa' => 'دویدن (اسم)'], null, [], [
            'gender' => GrammaticalGender::Masculine,
            'article' => GermanArticle::Der,
            'plural' => 'Läufe',
        ]);

        $verbLexeme = $this->createLexeme($de, 'laufen', ['slug' => 'laufen-de']);
        foreach (['läuft', 'lief', 'gelaufen'] as $form) {
            $this->addForm($verbLexeme, $form);
        }
        $this->addSense($verbLexeme, $verb, 'To run; to walk quickly.', $en, $b1, $en, $fa, ['en' => 'to run', 'fa' => 'دویدن']);
    }
}
