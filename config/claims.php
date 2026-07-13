<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max claims per hour
    |--------------------------------------------------------------------------
    |
    | How many claims a user may send within the rolling rate-limit window.
    | Override via CLAIM_MAX_PER_HOUR in .env for local testing.
    |
    */

    'max_per_hour' => (int) env('CLAIM_MAX_PER_HOUR', 3),

    /*
    |--------------------------------------------------------------------------
    | Rate limit window (seconds)
    |--------------------------------------------------------------------------
    |
    | Length of the rolling window used for claim send rate limiting.
    |
    */

    'rate_limit_window_seconds' => (int) env('CLAIM_RATE_LIMIT_WINDOW_SECONDS', 3600),

];
