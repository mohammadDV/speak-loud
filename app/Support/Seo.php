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
    public const SITE_NAME = 'SpeakLoudMate';

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
        return 'SpeakLoudMate connects language learners with partners for scheduled practice sessions. Publish slots, accept claims, and practice with real people.';
    }

    public static function defaultImage(): string
    {
        // PNG is the primary social share image for widest crawler support.
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
        $title = self::postTitle($post);
        $description = self::postDescription($post);
        $image = $post->coverUrl();
        $url = route('blog.show', $post->slug);

        self::share([
            'seoTitle' => $title,
            'seoDescription' => $description,
            'seoImage' => $image,
            'seoImageAlt' => $post->coverAlt(),
            'seoUrl' => $url,
            'seoType' => 'article',
            'seoPublishedTime' => $post->published_at?->toAtomString(),
            'seoModifiedTime' => $post->updated_at?->toAtomString(),
            'seoAuthor' => $authorName,
            'seoJsonLd' => [
                self::organizationSchema(),
                self::websiteSchema(),
                self::articleSchema($post, $title, $description, $image, $authorName),
                self::breadcrumbSchema([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Blog', 'url' => route('blog.index')],
                    ['name' => $title, 'url' => $url],
                ]),
            ],
        ]);
    }

    public static function postTitle(BlogPost $post): string
    {
        $metaTitle = trim(strip_tags((string) $post->meta_title));

        return $metaTitle !== '' ? $metaTitle : $post->title;
    }

    public static function postDescription(BlogPost $post): string
    {
        $metaDescription = trim((string) $post->meta_description);

        if ($metaDescription !== '') {
            return self::descriptionFromText($metaDescription);
        }

        return self::descriptionFromText($post->excerpt ?: strip_tags($post->body));
    }

    /**
     * @param  Collection<int, Faq>|iterable<int, Faq>  $faqs
     */
    public static function forFaq(iterable $faqs): void
    {
        $faqItems = Collection::make($faqs);

        self::share([
            'seoTitle' => 'FAQ',
            'seoDescription' => 'Answers to common questions about SpeakLoudMate schedules, claims, accounts, and safety.',
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
                'seoDescription' => 'This SpeakLoudMate profile is private.',
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
            : "{$displayName} on SpeakLoudMate — practice languages with real people.";
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

    public static function logoUrl(): string
    {
        return asset('images/logo-mark.png');
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
            'logo' => [
                '@type' => 'ImageObject',
                'url' => self::logoUrl(),
                'width' => 734,
                'height' => 512,
            ],
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
    public static function articleSchema(BlogPost $post, string $title, string $description, string $image, ?string $authorName): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $title,
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
                    'url' => self::logoUrl(),
                    'width' => 734,
                    'height' => 512,
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
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
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

    /**
     * @param  list<array{name: string, text: string, image?: string|null}>  $steps
     * @return array<string, mixed>
     */
    public static function howToSchema(string $name, string $description, array $steps): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $name,
            'description' => $description,
            'step' => collect($steps)->values()->map(function (array $step, int $index): array {
                $item = [
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'name' => $step['name'],
                    'text' => $step['text'],
                ];

                if (! empty($step['image'])) {
                    $item['image'] = $step['image'];
                }

                return $item;
            })->all(),
        ];
    }
}
