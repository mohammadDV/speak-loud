<?php

use App\Support\HomeDemoVideo;

test('home demo video returns null for empty url', function () {
    expect(HomeDemoVideo::embed(''))->toBeNull()
        ->and(HomeDemoVideo::embed('   '))->toBeNull();
});

test('home demo video parses youtube urls', function () {
    expect(HomeDemoVideo::embed('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
        ->toMatchArray([
            'type' => 'youtube',
            'src' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        ]);

    expect(HomeDemoVideo::embed('https://youtu.be/dQw4w9WgXcQ'))
        ->toMatchArray([
            'type' => 'youtube',
            'src' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        ]);
});

test('home demo video parses vimeo urls', function () {
    expect(HomeDemoVideo::embed('https://vimeo.com/123456789'))
        ->toMatchArray([
            'type' => 'vimeo',
            'src' => 'https://player.vimeo.com/video/123456789',
        ]);
});

test('home demo video treats other urls as file sources', function () {
    expect(HomeDemoVideo::embed('https://cdn.example.com/demo.mp4'))
        ->toMatchArray([
            'type' => 'file',
            'src' => 'https://cdn.example.com/demo.mp4',
        ]);
});

test('home demo video rejects non http file sources', function () {
    expect(HomeDemoVideo::embed('javascript:alert(1)'))->toBeNull()
        ->and(HomeDemoVideo::embed('data:text/html;base64,abc'))->toBeNull()
        ->and(HomeDemoVideo::embed('/local/demo.mp4'))->toBeNull();
});
