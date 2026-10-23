<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\UserProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class SitemapBuilder
{
    public const CACHE_KEY = 'sitemap.xml';

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function toXml(): string
    {
        $urls = array_merge(
            $this->staticUrls(),
            $this->blogPostUrls(),
            $this->profileUrls(),
        );

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.e($url['loc']).'</loc>';

            if (! empty($url['lastmod'])) {
                $lines[] = '    <lastmod>'.e($url['lastmod']).'</lastmod>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    private function staticUrls(): array
    {
        return [
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('about'), 'lastmod' => null],
            ['loc' => route('contact'), 'lastmod' => null],
            ['loc' => route('support'), 'lastmod' => null],
            ['loc' => route('terms'), 'lastmod' => null],
            ['loc' => route('blog.index'), 'lastmod' => null],
            ['loc' => route('faq.index'), 'lastmod' => null],
            ['loc' => route('discover'), 'lastmod' => null],
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    private function blogPostUrls(): array
    {
        $urls = [];

        BlogPost::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->select(['id', 'slug', 'updated_at', 'published_at'])
            ->chunkById(500, function ($posts) use (&$urls) {
                foreach ($posts as $post) {
                    /** @var BlogPost $post */
                    $urls[] = [
                        'loc' => route('blog.show', $post->slug),
                        'lastmod' => $this->formatLastmod($post->updated_at ?? $post->published_at),
                    ];
                }
            });

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    private function profileUrls(): array
    {
        $urls = [];

        UserProfile::query()
            ->where('is_private', false)
            ->whereNotNull('profile_slug')
            ->where('profile_slug', '!=', '')
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->orderBy('id')
            ->select(['id', 'profile_slug', 'updated_at'])
            ->chunkById(500, function ($profiles) use (&$urls) {
                foreach ($profiles as $profile) {
                    /** @var UserProfile $profile */
                    $urls[] = [
                        'loc' => route('users.show', $profile->profile_slug),
                        'lastmod' => $this->formatLastmod($profile->updated_at),
                    ];
                }
            });

        return $urls;
    }

    private function formatLastmod(?CarbonInterface $date): ?string
    {
        return $date?->toAtomString();
    }
}
