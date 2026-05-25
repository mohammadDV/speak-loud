<?php

namespace App\Support;

use App\Models\BlogPost;
use Illuminate\Support\Str;

class Seo
{
    public const SITE_NAME = 'SpeakLoud';

    public static function pageTitle(?string $pageTitle): string
    {
        if ($pageTitle === null || $pageTitle === '') {
            return self::SITE_NAME;
        }

        if (Str::contains($pageTitle, self::SITE_NAME)) {
            return $pageTitle;
        }

        return "{$pageTitle} | ".self::SITE_NAME;
    }

    public static function defaultDescription(): string
    {
        return 'SpeakLoud connects language learners with partners for scheduled practice sessions. Publish slots, accept claims, and practice with real people.';
    }

    public static function defaultImage(): string
    {
        return asset('images/og-default.svg');
    }

    public static function share(array $overrides = []): void
    {
        view()->share(array_merge([
            'seoTitle'       => null,
            'seoDescription' => self::defaultDescription(),
            'seoImage'       => self::defaultImage(),
            'seoUrl'         => url()->current(),
            'seoType'        => 'website',
            'seoRobots'      => null,
        ], $overrides));
    }

    public static function forPost(BlogPost $post): void
    {
        self::share([
            'seoTitle'       => $post->title,
            'seoDescription' => self::descriptionFromText($post->excerpt ?: strip_tags($post->body)),
            'seoImage'       => $post->coverUrl(),
            'seoUrl'         => route('blog.show', $post->slug),
            'seoType'        => 'article',
        ]);
    }

    public static function descriptionFromText(?string $text, int $limit = 160): string
    {
        $text = trim(strip_tags((string) $text));

        if ($text === '') {
            return self::defaultDescription();
        }

        return Str::limit($text, $limit);
    }

}
