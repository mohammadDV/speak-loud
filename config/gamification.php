<?php

return [

    'enabled' => true,

    // Shared weekly competition boundaries.
    'competition_timezone' => 'Europe/Berlin',

    // Initial timezone of new profiles; users can change it.
    'default_user_timezone' => 'Europe/Berlin',

    'processing' => [
        'mode' => 'sync', // 'sync' | 'queue'
    ],

    // Global scoring defaults (singular keys).
    'xp' => [
        'correct_answer' => 2,
        'quiz_completion' => 5,
        'question_milestone' => 5,
        'perfect_quiz' => 5,
        'daily_goal_completed' => 10,
    ],

    'rewards' => [
        'minimum_questions' => 5,
        'question_milestone_size' => 10,
        'daily_activity_xp_cap' => 300,
    ],

    'activities' => [
        'article_quiz' => [
            'label' => 'Article Quiz',
            'url' => '/learn/der-die-das',
            'flag_context' => 'article',
            'seconds_per_question' => 18,
            'qualifies_for_streak' => true,
            'xp' => [],
            'modes' => [],
        ],
        'meaning_quiz' => [
            'label' => 'Vocabulary Meaning',
            'url' => '/learn/vocabulary-meaning',
            'flag_context' => 'meaning',
            'seconds_per_question' => 18,
            'qualifies_for_streak' => true,
            'xp' => [],
            'modes' => [
                'multiple_choice' => ['xp' => []],
                'write_in' => ['xp' => []],
            ],
        ],
    ],

    'daily_goal' => [
        'default' => 20,
        'minimum' => 5,
        'maximum' => 100,
        'presets' => [
            'easy' => 10,
            'medium' => 20,
            'hard' => 40,
        ],
    ],

    'streak' => [
        'enabled' => true,
        'minimum_questions' => 5,
    ],

    'leaderboard' => [
        'enabled' => true,
        'public' => false,
        'default_visible' => false,
        'preview_limit' => 10,
        'cache_ttl' => 60,
        'historic_cache_ttl' => 3600,
    ],

    'recommendations' => [
        'enabled' => true,
        'short_quiz_questions' => 5,
        'default_questions' => 10,
        'focus_questions_max' => 10,
        'important_words_min' => 5,
        'difficult_words_min' => 3,
        'difficult_min_mistakes' => 2,
        'difficult_window_days' => 30,
        'welcome_back_after_days' => 2,
        'activity_usage_window_days' => 30,
    ],

    'achievements' => [
        'enabled' => false,
    ],

];
