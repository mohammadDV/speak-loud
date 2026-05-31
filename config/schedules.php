<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max slots per user
    |--------------------------------------------------------------------------
    |
    | How many schedule slots a user may own at once (active, inactive, or
    | cancelled — soft-deleted slots do not count). Override via
    | SCHEDULE_MAX_SLOTS_PER_USER in .env for local testing.
    |
    | Future: resolve per-user limits from a subscription plan instead of
    | this global default (see App\Support\ScheduleLimits).
    |
    */

    'max_slots_per_user' => (int) env('SCHEDULE_MAX_SLOTS_PER_USER', 3),

];
