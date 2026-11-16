<?php

use App\Domain\Learning\Enums\MeaningQuizType;
use App\Domain\Learning\Models\MeaningQuizAnswer;
use App\Domain\Learning\Models\MeaningQuizSession;
use App\Models\User;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

test('guests cannot view meaning quiz history', function () {
    $this->get(route('learn.vocabulary-meaning.history'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view meaning quiz history and expand session details', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $session = MeaningQuizSession::factory()->create([
        'user_id' => $user->id,
        'quiz_type' => MeaningQuizType::WriteIn,
        'word_count' => 2,
        'correct_count' => 1,
        'incorrect_count' => 1,
        'percentage' => 50,
        'translation_language_code' => 'en',
        'level_codes' => ['A1'],
        'category_codes' => ['family'],
        'sense_ids' => [1, 2],
    ]);

    MeaningQuizAnswer::factory()->create([
        'meaning_quiz_session_id' => $session->id,
        'sense_id' => 1,
        'lemma' => 'Haus',
        'correct_meaning' => 'house',
        'selected_meaning' => 'house',
        'is_correct' => true,
        'match_score' => 100,
        'position' => 0,
    ]);

    MeaningQuizAnswer::factory()->create([
        'meaning_quiz_session_id' => $session->id,
        'sense_id' => 2,
        'lemma' => 'Baum',
        'correct_meaning' => 'tree',
        'selected_meaning' => 'flower',
        'is_correct' => false,
        'match_score' => 20,
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('learn.vocabulary-meaning.history'))
        ->assertOk()
        ->assertSee('Meaning quiz progress')
        ->assertSee('50%')
        ->assertSee('Type the meaning')
        ->assertSee('Important')
        ->assertSee('Learned');

    Volt::actingAs($user)
        ->test('learn.vocabulary-meaning-history')
        ->call('openSession', $session->id)
        ->assertSet('selected_session_id', $session->id)
        ->assertSee('Haus')
        ->assertSee('house')
        ->assertSee('Baum')
        ->assertSee('Your answer: flower')
        ->assertSee('Correct:')
        ->assertSee('tree');
});

test('users cannot open another users meaning quiz session', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $intruder = User::factory()->create(['email_verified_at' => now()]);

    $session = MeaningQuizSession::factory()->create([
        'user_id' => $owner->id,
        'quiz_type' => MeaningQuizType::MultipleChoice,
        'word_count' => 1,
        'correct_count' => 1,
        'incorrect_count' => 0,
        'percentage' => 100,
        'translation_language_code' => 'en',
        'sense_ids' => [1],
    ]);

    MeaningQuizAnswer::factory()->create([
        'meaning_quiz_session_id' => $session->id,
        'sense_id' => 1,
        'lemma' => 'Haus',
        'correct_meaning' => 'house',
        'selected_meaning' => 'house',
        'is_correct' => true,
        'match_score' => 100,
        'position' => 0,
    ]);

    Volt::actingAs($intruder)
        ->test('learn.vocabulary-meaning-history')
        ->call('openSession', $session->id)
        ->assertSet('selected_session_id', null)
        ->assertSet('selected_answers', [])
        ->assertDontSee('Haus');
});
