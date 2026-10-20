<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Seo
{
    public const SITE_NAME = 'SpeakLoud';

    public static function pageTitle(?string $pageTitle): string
    {
        if ($pageTitle === null || $pageTitle === '') {
            return self::SITE_NAME;
        }

        // Don't double-append if the brand is already appended as a suffix.
        if (Str::endsWith($pageTitle, ' | '.self::SITE_NAME) || Str::endsWith($pageTitle, ' — '.self::SITE_NAME)) {
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
        return asset('images/og-default.png');
    }

    public static function defaultImageWidth(): int
    {
        return (int) config('seo.image_width', 1200);
    }

    public static function defaultImageHeight(): int
    {
        return (int) config('seo.image_height', 630);
    }

    public static function twitterSite(): ?string
    {
        $handle = config('seo.twitter_site');

        if (! is_string($handle) || trim($handle) === '') {
            return null;
        }

        return str_starts_with($handle, '@') ? $handle : '@'.$handle;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function share(array $overrides = []): void
    {
        view()->share(array_merge([
            'seoTitle' => null,
            'seoDescription' => self::defaultDescription(),
            'seoImage' => self::defaultImage(),
            'seoImageAlt' => self::SITE_NAME,
            'seoImageWidth' => self::defaultImageWidth(),
            'seoImageHeight' => self::defaultImageHeight(),
            'seoUrl' => url()->current(),
            'seoType' => 'website',
            'seoRobots' => null,
            'seoPublishedTime' => null,
            'seoModifiedTime' => null,
            'seoAuthor' => null,
            'seoJsonLd' => self::defaultJsonLd(),
        ], $overrides));
    }

    public static function forPost(BlogPost $post): void
    {
        $post->loadMissing(['author.profile']);

        $authorProfile = $post->author?->profile;
        $authorName = $authorProfile instanceof UserProfile
            ? $authorProfile->display_name
            : null;
        $description = self::descriptionFromText($post->excerpt ?: strip_tags($post->body));
        $image = $post->coverUrl();

        self::share([
            'seoTitle' => $post->title,
            'seoDescription' => $description,
            'seoImage' => $image,
            'seoImageAlt' => $post->coverAlt(),
            'seoUrl' => route('blog.show', $post->slug),
            'seoType' => 'article',
            'seoPublishedTime' => $post->published_at?->toAtomString(),
            'seoModifiedTime' => $post->updated_at?->toAtomString(),
            'seoAuthor' => $authorName,
            'seoJsonLd' => [
                self::organizationSchema(),
                self::websiteSchema(),
                self::articleSchema($post, $description, $image, $authorName),
            ],
        ]);
    }

    /**
     * @param  Collection<int, Faq>|iterable<int, Faq>  $faqs
     */
    public static function forFaq(iterable $faqs): void
    {
        $faqItems = Collection::make($faqs);

        self::share([
            'seoTitle' => 'FAQ',
            'seoDescription' => 'Answers to common questions about SpeakLoud schedules, claims, accounts, and safety.',
            'seoUrl' => route('faq.index'),
            'seoJsonLd' => [
                self::organizationSchema(),
                self::websiteSchema(),
                self::faqPageSchema($faqItems),
            ],
        ]);
    }

    public static function forProfile(User $user): void
    {
        $profile = $user->profile;

        if (! $profile instanceof UserProfile) {
            return;
        }

        $displayName = $profile->display_name ?: $profile->username ?: 'Member';
        $isPrivate = (bool) $profile->is_private;

        if ($isPrivate) {
            self::share([
                'seoTitle' => $displayName,
                'seoDescription' => 'This SpeakLoud profile is private.',
                'seoImage' => self::defaultImage(),
                'seoImageAlt' => self::SITE_NAME,
                'seoUrl' => route('users.show', $profile->profile_slug),
                'seoType' => 'profile',
                'seoRobots' => 'noindex, nofollow',
                'seoJsonLd' => [
                    self::organizationSchema(),
                    self::websiteSchema(),
                ],
            ]);

            return;
        }

        $bio = trim(strip_tags((string) $profile->bio));
        $description = $bio !== ''
            ? self::descriptionFromText($bio)
            : "{$displayName} on SpeakLoud — practice languages with real people.";
        $image = $profile->profileImageUrl()
            ?? $profile->backgroundImageUrl()
            ?? self::defaultImage();

        self::share([
            'seoTitle' => $displayName,
            'seoDescription' => $description,
            'seoImage' => $image,
            'seoImageAlt' => "Profile picture of {$displayName}",
            'seoUrl' => route('users.show', $profile->profile_slug),
            'seoType' => 'profile',
            'seoRobots' => null,
            'seoJsonLd' => [
                self::organizationSchema(),
                self::websiteSchema(),
                self::personSchema($displayName, $description, $image, route('users.show', $profile->profile_slug)),
            ],
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

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaultJsonLd(): array
    {
        return [
            self::organizationSchema(),
            self::websiteSchema(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => self::SITE_NAME,
            'url' => url('/'),
            'logo' => self::defaultImage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::SITE_NAME,
            'url' => url('/'),
            'description' => self::defaultDescription(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function articleSchema(BlogPost $post, string $description, string $image, ?string $authorName): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $description,
            'image' => [$image],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('blog.show', $post->slug),
            ],
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => self::SITE_NAME,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::defaultImage(),
                ],
            ],
        ];

        if ($authorName) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $authorName,
            ];
        }

        return $schema;
    }

    /**
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>
     */
    public static function faqPageSchema(Collection $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq) => [
                '@type' => 'Question',
                'name' => strip_tags($faq->question),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq->answer),
                ],
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function personSchema(string $name, string $description, string $image, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $name,
            'description' => $description,
            'image' => $image,
            'url' => $url,
        ];
    }
}
