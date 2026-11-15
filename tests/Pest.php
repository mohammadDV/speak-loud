<?php

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\GrammaticalGender;
use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\GermanNounDetail;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\PartOfSpeech;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseLevel;
use App\Domain\Dictionary\Models\SenseTranslation;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

uses(RefreshDatabase::class)->in('Feature');

function actingAsUser(): User
{
    $user = User::factory()->create();
    test()->actingAs($user);

    return $user;
}

/**
 * @return list<Sense>
 */
function createPublishedGermanNouns(int $count, array $options = []): array
{
    $de = Language::query()->where('code', 'de')->firstOrFail();
    $en = Language::query()->where('code', 'en')->firstOrFail();
    $noun = PartOfSpeech::query()->where('code', 'noun')->firstOrFail();
    $levelCode = $options['level'] ?? 'A1';
    $categoryCode = $options['category'] ?? 'family';
    $level = DifficultyLevel::query()->where('code', $levelCode)->firstOrFail();
    $category = Category::query()->where('code', $categoryCode)->firstOrFail();

    $articles = [
        GermanArticle::Der,
        GermanArticle::Die,
        GermanArticle::Das,
    ];
    $genders = [
        GrammaticalGender::Masculine,
        GrammaticalGender::Feminine,
        GrammaticalGender::Neuter,
    ];

    $senses = [];

    for ($i = 0; $i < $count; $i++) {
        $article = $articles[$i % 3];
        $gender = $genders[$i % 3];
        $lemma = ($options['lemma_prefix'] ?? 'Wort').$i.fake()->unique()->numerify('###');

        $lexeme = Lexeme::factory()->published()->forLanguage($de)->create([
            'lemma' => $lemma,
            'normalized_lemma' => TextNormalizer::normalize($lemma, 'de'),
        ]);
        $lexeme->categories()->sync([$category->id]);

        $sense = Sense::factory()->published()->create([
            'lexeme_id' => $lexeme->id,
            'part_of_speech_id' => $noun->id,
            'definition' => 'Test noun '.$i,
            'definition_language_id' => $en->id,
        ]);

        GermanNounDetail::factory()->create([
            'sense_id' => $sense->id,
            'article' => $article,
            'grammatical_gender' => $gender,
            'plural_form' => $lemma.'e',
        ]);

        SenseLevel::factory()->create([
            'sense_id' => $sense->id,
            'difficulty_level_id' => $level->id,
            'is_primary' => true,
        ]);

        SenseTranslation::factory()->published()->create([
            'sense_id' => $sense->id,
            'target_language_id' => $en->id,
            'translation_text' => 'word '.$i,
        ]);

        $senses[] = $sense->fresh(['germanNounDetail', 'lexeme']);
    }

    return $senses;
}
