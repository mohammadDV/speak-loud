<?php

use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Models\GrammarQuizAnswer;
use App\Domain\Learning\Models\GrammarQuizSession;
use App\Domain\Learning\Models\LessonQuestion;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests are redirected from grammar history', function () {
    $this->get(route('learn.grammar.history'))
        ->assertRedirect(route('login'));
});

test('authenticated verified users can view grammar quiz history', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
    $question = LessonQuestion::factory()->create();

    $session = GrammarQuizSession::factory()->create([
        'user_id' => $user->id,
        'lesson_ids' => [$question->lesson_id],
        'word_count' => 1,
        'quiz_type' => GrammarQuizType::MultipleChoice,
        'correct_count' => 1,
        'incorrect_count' => 0,
        'percentage' => 100,
        'question_ids' => [$question->id],
    ]);

    GrammarQuizAnswer::factory()->create([
        'grammar_quiz_session_id' => $session->id,
        'lesson_question_id' => $question->id,
        'prompt' => 'Conjugate kommen for du: du ___.',
        'correct_answer' => 'kommst',
        'selected_answer' => 'kommst',
        'is_correct' => true,
        'position' => 1,
    ]);

    Volt::actingAs($user)
        ->test('learn.grammar.history')
        ->assertSee('Grammar history')
        ->assertSee('100%')
        ->call('selectSession', $session->id)
        ->assertSet('selected_session_id', $session->id)
        ->assertSee('Conjugate kommen for du');
});
