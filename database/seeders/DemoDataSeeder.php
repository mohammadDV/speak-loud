<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\Language;
use App\Models\Message;
use App\Models\Report;
use App\Models\Schedule;
use App\Models\ScheduleOneTimeSlot;
use App\Models\ScheduleRecurringRule;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserLanguage;
use App\Models\UserNotificationPreference;
use App\Models\UserProfile;
use App\Models\UserTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const PASSWORD = '123456789';

    private const USER_COUNT = 100;

    /** @var list<string> */
    private array $levels = [
        'beginner', 'elementary', 'intermediate',
        'upper_intermediate', 'advanced', 'fluent',
    ];

    /** @var list<string> */
    private array $countryCodes = ['US', 'GB', 'DE', 'FR', 'ES', 'IT', 'IR', 'TR', 'JP', 'BR', 'CA', 'NL', 'SE', 'PL', 'IN'];

    /** @var list<string> */
    private array $genders = ['male', 'female', 'non_binary', 'prefer_not_to_say'];

    /** @var list<string> */
    private array $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    public function run(): void
    {
        $languages = Language::where('is_active', true)->get();
        $interests = Interest::all();

        if ($languages->isEmpty() || $interests->isEmpty()) {
            $this->command?->warn('Run LanguageSeeder and InterestSeeder first.');

            return;
        }

        $users = $this->seedUsers();
        $admin = $users->first();

        app(ContentSeeder::class)->seedBlogPosts($admin);

        $this->seedUserRelations($users, $languages, $interests);
        $schedules = $this->seedSchedules($users, $languages);
        $this->seedClaimsAndConversations($users, $schedules);
        $this->seedTickets($users);
        $this->seedBlocksAndReports($users, $admin);

        $this->command?->info('Demo data seeded: '.self::USER_COUNT.' users (password: '.self::PASSWORD.').');
        $this->command?->info('Admin login: admin@speakloud.test / '.self::PASSWORD);
        $this->command?->info('Sample user: user1@speakloud.test / '.self::PASSWORD);
    }

    /** @return Collection<int, User> */
    private function seedUsers(): Collection
    {
        $users = collect();

        $admin = User::firstOrCreate(
            ['email' => 'admin@speakloud.test'],
            [
                'uuid'              => Str::uuid()->toString(),
                'password'          => self::PASSWORD,
                'role'              => 'admin',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        UserProfile::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'username'     => 'admin',
                'display_name' => 'SpeakLoud Admin',
                'bio'          => 'Platform administrator account for local development.',
                'nationality'  => 'Global',
                'country_code' => 'US',
                'is_available' => true,
            ]
        );

        $users->push($admin);

        for ($i = 1; $i <= self::USER_COUNT; $i++) {
            $email = "user{$i}@speakloud.test";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'uuid'              => Str::uuid()->toString(),
                    'password'          => self::PASSWORD,
                    'role'              => 'user',
                    'status'            => 'active',
                    'email_verified_at' => now()->subDays(rand(1, 90)),
                    'last_login_at'     => now()->subDays(rand(0, 14)),
                ]
            );

            $firstName = fake()->firstName();
            $username = 'user'.$i;

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'username'     => $username,
                    'display_name' => $firstName.' '.fake()->lastName(),
                    'bio'          => fake()->optional(0.85)->paragraph(),
                    'gender'       => fake()->randomElement($this->genders),
                    'birthdate'    => fake()->optional(0.7)->dateTimeBetween('-55 years', '-18 years')?->format('Y-m-d'),
                    'nationality'  => fake()->country(),
                    'country_code' => fake()->randomElement($this->countryCodes),
                    'is_available' => fake()->boolean(85),
                ]
            );

            $users->push($user);
        }

        return $users->unique('id')->values();
    }

    private function seedUserRelations(Collection $users, Collection $languages, Collection $interests): void
    {
        $notificationTypes = ['claim_received', 'claim_accepted', 'new_message', 'schedule_reminder'];

        foreach ($users as $user) {
            $nativeLang = $languages->random();
            $learningLang = $languages->where('id', '!=', $nativeLang->id)->random();

            UserLanguage::firstOrCreate(
                ['user_id' => $user->id, 'language_id' => $nativeLang->id, 'type' => 'native'],
                ['level' => 'fluent']
            );

            UserLanguage::firstOrCreate(
                ['user_id' => $user->id, 'language_id' => $learningLang->id, 'type' => 'learning'],
                ['level' => fake()->randomElement($this->levels)]
            );

            if (fake()->boolean(40)) {
                $extra = $languages->whereNotIn('id', [$nativeLang->id, $learningLang->id])->random();
                UserLanguage::firstOrCreate(
                    ['user_id' => $user->id, 'language_id' => $extra->id, 'type' => 'learning'],
                    ['level' => fake()->randomElement($this->levels)]
                );
            }

            $user->interests()->syncWithoutDetaching(
                $interests->random(rand(2, 5))->pluck('id')->all()
            );

            foreach (fake()->randomElements(
                ['polyglot', 'beginner-friendly', 'evening', 'weekend', 'patient', 'structured'],
                rand(1, 3)
            ) as $tag) {
                UserTag::firstOrCreate(['user_id' => $user->id, 'tag' => $tag]);
            }

            foreach ($notificationTypes as $type) {
                foreach (['email', 'push'] as $channel) {
                    UserNotificationPreference::firstOrCreate(
                        [
                            'user_id'           => $user->id,
                            'notification_type' => $type,
                            'channel'           => $channel,
                        ],
                        ['is_enabled' => fake()->boolean(80)]
                    );
                }
            }
        }
    }

    /** @return Collection<int, Schedule> */
    private function seedSchedules(Collection $users, Collection $languages): Collection
    {
        $schedules = collect();
        $hosts = $users->shuffle()->take(45);

        foreach ($hosts as $host) {
            $language = $languages->random();

            if (fake()->boolean(55)) {
                $schedule = Schedule::create([
                    'user_id'          => $host->id,
                    'title'            => fake()->randomElement(['Weekly chat', 'Saturday practice', 'Morning session']),
                    'description'      => fake()->optional()->sentence(),
                    'type'             => 'recurring',
                    'language_id'      => $language->id,
                    'max_participants' => fake()->numberBetween(1, 3),
                    'status'           => 'active',
                ]);

                ScheduleRecurringRule::create([
                    'schedule_id'  => $schedule->id,
                    'day_of_week'  => implode(',', fake()->randomElements($this->weekdays, rand(1, 3))),
                    'start_time'   => sprintf('%02d:00:00', rand(8, 20)),
                    'end_time'     => sprintf('%02d:00:00', rand(9, 21)),
                    'valid_from'   => now()->subMonth()->toDateString(),
                    'valid_until'  => now()->addMonths(3)->toDateString(),
                ]);

                $schedules->push($schedule);
            }

            if (fake()->boolean(50)) {
                $start = now()->addDays(rand(1, 21))->setTime(rand(9, 19), 0);
                $schedule = Schedule::create([
                    'user_id'          => $host->id,
                    'title'            => fake()->randomElement(['One-off session', 'Trial call', 'Open slot']),
                    'description'      => fake()->optional()->sentence(),
                    'type'             => 'one_time',
                    'language_id'      => $language->id,
                    'max_participants' => 1,
                    'status'           => 'active',
                ]);

                ScheduleOneTimeSlot::create([
                    'schedule_id'    => $schedule->id,
                    'start_datetime' => $start,
                    'end_datetime'   => $start->copy()->addHour(),
                ]);

                $schedules->push($schedule);
            }
        }

        return $schedules;
    }

    private function seedClaimsAndConversations(Collection $users, Collection $schedules): void
    {
        $statuses = ['pending', 'accepted', 'rejected', 'withdrawn'];
        $usedPairs = [];
        $acceptedClaims = 0;

        foreach ($schedules->shuffle()->take(70) as $schedule) {
            $sender = $users->where('id', '!=', $schedule->user_id)->random();
            $pairKey = $sender->id.'-'.$schedule->id;

            if (isset($usedPairs[$pairKey])) {
                continue;
            }

            $usedPairs[$pairKey] = true;

            $status = $statuses[array_rand($statuses)];

            if ($acceptedClaims >= 25 && $status === 'accepted') {
                $status = 'pending';
            }

            $claim = Claim::create([
                'sender_id'    => $sender->id,
                'receiver_id'  => $schedule->user_id,
                'schedule_id'  => $schedule->id,
                'type'         => 'schedule',
                'status'       => $status,
                'message'      => fake()->optional(0.7)->sentence(),
                'responded_at' => in_array($status, ['accepted', 'rejected'], true) ? now()->subDays(rand(0, 5)) : null,
                'expires_at'   => now()->addDays(7),
            ]);

            if ($status === 'accepted') {
                $acceptedClaims++;
                $this->createConversationWithMessages($claim);
            }
        }

        foreach ($users->shuffle()->take(15) as $sender) {
            $receiver = $users->where('id', '!=', $sender->id)->random();

            Claim::create([
                'sender_id'    => $sender->id,
                'receiver_id'  => $receiver->id,
                'schedule_id'  => null,
                'type'         => 'direct',
                'status'       => fake()->randomElement(['pending', 'accepted', 'rejected']),
                'message'      => fake()->sentence(),
                'responded_at' => fake()->optional()->dateTimeBetween('-3 days', 'now'),
                'expires_at'   => now()->addDays(5),
            ]);
        }
    }

    private function createConversationWithMessages(Claim $claim): void
    {
        $conversation = Conversation::create([
            'claim_id'        => $claim->id,
            'user_a_id'       => $claim->receiver_id,
            'user_b_id'       => $claim->sender_id,
            'last_message_at' => now(),
        ]);
        $conversation->created_at = now()->subDays(rand(1, 10));
        $conversation->save();

        $participants = [$claim->sender_id, $claim->receiver_id];
        $messageCount = rand(3, 12);
        $lastAt = $conversation->created_at->copy();

        for ($i = 0; $i < $messageCount; $i++) {
            $senderId = $participants[$i % 2];
            $lastAt = $lastAt->copy()->addMinutes(rand(5, 180));

            $message = new Message([
                'conversation_id' => $conversation->id,
                'sender_id'       => $senderId,
                'body'            => fake()->sentence(),
                'type'            => 'text',
                'is_read'         => $i < $messageCount - 1,
                'read_at'         => $i < $messageCount - 1 ? $lastAt : null,
            ]);
            $message->created_at = $lastAt;
            $message->save();
        }

        $conversation->update(['last_message_at' => $lastAt]);
    }

    private function seedTickets(Collection $users): void
    {
        $categories = TicketCategory::pluck('id');
        $adminId = User::where('email', 'admin@speakloud.test')->value('id');

        foreach ($users->shuffle()->take(12) as $user) {
            $ticket = Ticket::create([
                'user_id'     => $user->id,
                'category_id' => $categories->random(),
                'subject'     => fake()->sentence(4),
                'status'      => fake()->randomElement(['open', 'in_progress', 'waiting_user', 'resolved']),
                'priority'    => fake()->randomElement(['low', 'normal', 'high']),
                'assigned_to' => fake()->boolean(40) ? $adminId : null,
            ]);

            TicketMessage::create([
                'ticket_id'  => $ticket->id,
                'sender_id'  => $user->id,
                'body'       => fake()->paragraph(),
                'is_internal'=> false,
                'created_at' => now()->subDays(rand(1, 5)),
            ]);

            if ($ticket->assigned_to) {
                TicketMessage::create([
                    'ticket_id'   => $ticket->id,
                    'sender_id'   => $ticket->assigned_to,
                    'body'        => 'Thanks for reaching out — we are looking into this.',
                    'is_internal' => false,
                    'created_at'  => now()->subDays(rand(0, 2)),
                ]);
            }
        }
    }

    private function seedBlocksAndReports(Collection $users, User $admin): void
    {
        $pairs = $users->shuffle()->take(8);

        for ($i = 0; $i < $pairs->count() - 1; $i += 2) {
            UserBlock::firstOrCreate([
                'blocker_id' => $pairs[$i]->id,
                'blocked_id' => $pairs[$i + 1]->id,
            ]);
        }

        foreach ($users->shuffle()->take(5) as $reported) {
            $reporter = $users->where('id', '!=', $reported->id)->random();

            Report::create([
                'reporter_id' => $reporter->id,
                'reported_id' => $reported->id,
                'reason'      => fake()->randomElement(['spam', 'harassment', 'inappropriate_content', 'fake_profile', 'other']),
                'description' => fake()->optional()->sentence(),
                'status'      => fake()->randomElement(['pending', 'reviewed', 'dismissed']),
                'reviewed_by' => fake()->boolean(30) ? $admin->id : null,
                'reviewed_at' => fake()->boolean(30) ? now()->subDays(rand(1, 7)) : null,
            ]);
        }
    }
}
