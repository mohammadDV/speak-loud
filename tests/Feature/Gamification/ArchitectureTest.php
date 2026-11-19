<?php

test('gamification does not import dictionary or quiz models', function () {
    $root = app_path('Domain/Gamification');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    $violations = [];

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        if ($contents === false) {
            continue;
        }

        if (preg_match('/use App\\\\Domain\\\\Dictionary\\\\/', $contents)) {
            $violations[] = $file->getPathname().' imports Dictionary';
        }

        if (preg_match('/use App\\\\Domain\\\\Learning\\\\Models\\\\/', $contents)) {
            $violations[] = $file->getPathname().' imports Learning models';
        }

        if (preg_match('/ArticleQuizXpService|MeaningQuizXpService|PluralQuizXpService/', $contents)) {
            $violations[] = $file->getPathname().' defines quiz-specific XP service';
        }
    }

    expect($violations)->toBeEmpty();
});

test('learning does not import gamification', function () {
    $root = app_path('Domain/Learning');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    $violations = [];

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        if ($contents === false) {
            continue;
        }

        if (preg_match('/use App\\\\Domain\\\\Gamification\\\\/', $contents)) {
            $violations[] = $file->getPathname();
        }
    }

    expect($violations)->toBeEmpty();
});
