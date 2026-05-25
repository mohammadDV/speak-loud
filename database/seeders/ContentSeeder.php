<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFaqCategories();
        $this->seedFaqs();
        $this->seedBlogCategories();
        $this->seedBlogTags();
        $this->seedTicketCategories();
    }

    private function seedFaqCategories(): void
    {
        foreach ([
            ['name' => 'Getting started', 'slug' => 'getting-started', 'sort_order' => 1],
            ['name' => 'Schedules & claims', 'slug' => 'schedules-claims', 'sort_order' => 2],
            ['name' => 'Account & safety', 'slug' => 'account-safety', 'sort_order' => 3],
        ] as $category) {
            FaqCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    private function seedFaqs(): void
    {
        $categories = FaqCategory::pluck('id', 'slug');

        $faqs = [
            ['getting-started', 'What is SpeakLoud?', 'SpeakLoud connects language learners with partners for scheduled or direct practice sessions.'],
            ['getting-started', 'Is SpeakLoud free?', 'Yes. Core features are free while we grow the community.'],
            ['schedules-claims', 'How do I publish a time slot?', 'Open Schedule, create a recurring or one-off slot, and set your practice language.'],
            ['schedules-claims', 'What happens when I accept a claim?', 'You share one chat thread with that person to coordinate before your session.'],
            ['account-safety', 'How do I block someone?', 'Visit their profile and use the block option. Blocked users never appear in search.'],
            ['account-safety', 'How do I report a user?', 'Use the report button on their profile or in chat. Our team reviews reports promptly.'],
        ];

        foreach ($faqs as $index => [$categorySlug, $question, $answer]) {
            Faq::firstOrCreate(
                ['question' => $question],
                [
                    'category_id' => $categories[$categorySlug] ?? null,
                    'answer'      => $answer,
                    'is_active'   => true,
                    'sort_order'  => $index + 1,
                ]
            );
        }
    }

    private function seedBlogCategories(): void
    {
        foreach ([
            ['name' => 'Tips & tricks', 'slug' => 'tips'],
            ['name' => 'Community', 'slug' => 'community'],
            ['name' => 'Product updates', 'slug' => 'updates'],
        ] as $category) {
            BlogCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    private function seedBlogTags(): void
    {
        foreach (['beginners', 'pronunciation', 'culture', 'motivation', 'grammar'] as $tag) {
            BlogTag::firstOrCreate(['slug' => $tag], ['name' => ucfirst($tag)]);
        }
    }

    private function seedTicketCategories(): void
    {
        foreach ([
            ['name' => 'Account', 'slug' => 'account'],
            ['name' => 'Billing', 'slug' => 'billing'],
            ['name' => 'Bug report', 'slug' => 'bug'],
            ['name' => 'Feature request', 'slug' => 'feature'],
        ] as $category) {
            TicketCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    public function seedBlogPosts(User $author): void
    {
        $categories = BlogCategory::pluck('id', 'slug');
        $tagIds = BlogTag::pluck('id');
        $covers = [
            'images/blog/partners.svg',
            'images/blog/first-session.svg',
            'images/blog/recurring.svg',
            'images/blog/interests.svg',
            'images/blog/welcome.svg',
            'images/blog/session-rules.svg',
        ];

        foreach ($this->blogPostDefinitions() as $index => $post) {
            $slug = Str::slug($post['title']);
            $cover = $post['cover'] ?? $covers[$index % count($covers)];

            $blogPost = BlogPost::firstOrCreate(
                ['slug' => $slug],
                [
                    'author_id'        => $author->id,
                    'category_id'      => $categories[$post['category']] ?? null,
                    'title'            => $post['title'],
                    'excerpt'          => $post['excerpt'],
                    'body'             => $post['body'],
                    'cover_image_path' => $cover,
                    'status'           => 'published',
                    'published_at'     => now()->subDays(30 - $index),
                ]
            );

            if (! $blogPost->cover_image_path) {
                $blogPost->update(['cover_image_path' => $cover]);
            }

            if ($blogPost->tags()->count() === 0 && $tagIds->isNotEmpty()) {
                $blogPost->tags()->sync($tagIds->random(min(2, $tagIds->count()))->all());
            }
        }

        $this->backfillBlogCoverImages();
    }

    /**
     * @return list<array{category: string, title: string, excerpt: string, body: string, cover?: string}>
     */
    private function blogPostDefinitions(): array
    {
        return [
            ['category' => 'tips', 'title' => 'Five ways to find the right practice partner', 'excerpt' => 'Filters, bios, and shared interests help you match with someone at your level.', 'body' => '<p>Start with language and level filters on Discover, then read bios for shared interests.</p>', 'cover' => 'images/blog/partners.svg'],
            ['category' => 'tips', 'title' => 'Making the most of your first session', 'excerpt' => 'A simple structure keeps first conversations comfortable and useful.', 'body' => '<p>Warm up for five minutes, agree on a topic, and leave time for feedback at the end.</p>', 'cover' => 'images/blog/first-session.svg'],
            ['category' => 'community', 'title' => 'Why recurring slots work better than one-offs', 'excerpt' => 'Consistency beats intensity when you are building long-term fluency.', 'body' => '<p>Weekly slots build habit and trust with the same partners over time.</p>', 'cover' => 'images/blog/recurring.svg'],
            ['category' => 'community', 'title' => 'How shared interests make practice easier', 'excerpt' => 'Talking about something you both enjoy lowers anxiety from day one.', 'body' => '<p>Add interests to your profile so others can find you on Discover.</p>', 'cover' => 'images/blog/interests.svg'],
            ['category' => 'updates', 'title' => 'Welcome to SpeakLoud schedules and group chat', 'excerpt' => 'Create slots, accept claims, and coordinate with your group in one place.', 'body' => '<p>Hosts publish availability; accepted members join a group chat for each slot.</p>', 'cover' => 'images/blog/welcome.svg'],
            ['category' => 'tips', 'title' => 'Setting session rules that partners actually read', 'excerpt' => 'Clear notes on your slot reduce no-shows and awkward mismatches.', 'body' => '<p>Mention camera on/off, language split, and tools in your session rules field.</p>', 'cover' => 'images/blog/session-rules.svg'],
            ['category' => 'tips', 'title' => 'Building confidence before you speak', 'excerpt' => 'Small habits before each call make speaking feel less intimidating.', 'body' => '<p>Review your topic, breathe, and remember that partners are learners too.</p>'],
            ['category' => 'tips', 'title' => 'How to handle awkward silences', 'excerpt' => 'Pauses are normal — have a few questions ready to restart the flow.', 'body' => '<p>Keep a short list of follow-up questions about hobbies, travel, or daily routines.</p>'],
            ['category' => 'tips', 'title' => 'Choosing between video and audio-only calls', 'excerpt' => 'Pick the format that matches your energy and privacy needs.', 'body' => '<p>State your preference in session rules so claims align with your comfort level.</p>'],
            ['category' => 'tips', 'title' => 'Writing a profile bio that gets claims', 'excerpt' => 'Be specific about languages, level, and what you enjoy discussing.', 'body' => '<p>Two or three concrete sentences beat a long list of vague goals.</p>'],
            ['category' => 'updates', 'title' => 'Understanding UTC times on SpeakLoud', 'excerpt' => 'All slot times are stored in UTC so partners worldwide see the same moment.', 'body' => '<p>Compare UTC with your local clock when you publish or claim a slot.</p>'],
            ['category' => 'community', 'title' => 'Group chat etiquette for hosts', 'excerpt' => 'A short welcome message sets tone before the session starts.', 'body' => '<p>Greet new members, confirm the meeting link, and recap language split expectations.</p>'],
            ['category' => 'tips', 'title' => 'When to decline a claim politely', 'excerpt' => 'It is okay to say no — a kind note keeps the community respectful.', 'body' => '<p>Thank the learner, mention a mismatch if helpful, and invite them to try another slot.</p>'],
            ['category' => 'community', 'title' => 'Practicing two languages in one week', 'excerpt' => 'Alternate focus days so neither language gets neglected.', 'body' => '<p>Publish separate slots per language instead of mixing both in one session.</p>'],
            ['category' => 'community', 'title' => 'Celebrating small speaking wins', 'excerpt' => 'Fluency grows from repeatable moments, not single marathon calls.', 'body' => '<p>Note one thing that went better each week — vocabulary, speed, or confidence.</p>'],
            ['category' => 'tips', 'title' => 'Finding beginner-friendly partners', 'excerpt' => 'Level filters and honest bios help you avoid painful mismatches.', 'body' => '<p>Look for hosts who mention patience, slow speech, or structured topics for beginners.</p>'],
            ['category' => 'tips', 'title' => 'Preparing topics the night before', 'excerpt' => 'Five minutes of prep reduces panic right before you join the call.', 'body' => '<p>Pick one article, photo, or question to bring — partners appreciate a clear starting point.</p>'],
            ['category' => 'tips', 'title' => 'Why feedback at the end of sessions matters', 'excerpt' => 'Two minutes of constructive notes compound over months of practice.', 'body' => '<p>Share one strength and one gentle suggestion; ask for the same in return.</p>'],
            ['category' => 'updates', 'title' => 'Discover filters: language, level, and interests', 'excerpt' => 'Narrow open slots to the partners who fit how you want to practice.', 'body' => '<p>Combine language and interest filters to surface hosts who share your hobbies.</p>'],
        ];
    }

    private function backfillBlogCoverImages(): void
    {
        $coversBySlug = [
            'five-ways-to-find-the-right-practice-partner' => 'images/blog/partners.svg',
            'making-the-most-of-your-first-session'        => 'images/blog/first-session.svg',
            'why-recurring-slots-work-better-than-one-offs'  => 'images/blog/recurring.svg',
            'how-shared-interests-make-practice-easier'    => 'images/blog/interests.svg',
            'welcome-to-speakloud-schedules-and-group-chat' => 'images/blog/welcome.svg',
            'setting-session-rules-that-partners-actually-read' => 'images/blog/session-rules.svg',
        ];

        BlogPost::query()
            ->where(fn ($q) => $q->whereNull('cover_image_path')->orWhere('cover_image_path', ''))
            ->each(function (BlogPost $post) use ($coversBySlug) {
                $post->update([
                    'cover_image_path' => $coversBySlug[$post->slug] ?? 'images/blog/default-cover.svg',
                ]);
            });
    }
}
