<?php

namespace App\Support;

class ScheduleDescription
{
    public const PLACEHOLDER = 'e.g. Video call on Google Meet. Be on time. We split 50/50 — half the session in each language. Camera on, quiet space.';

    /** @var list<string> */
    public const EXAMPLES = [
        'Video call on Google Meet. 50/50 language split. Please be on time and use a quiet space.',
        'Voice call only. Beginner-friendly. We correct each other gently — no interrupting.',
        'Camera on. Topic: daily life and travel. Link sent in chat after your claim is accepted.',
        'Structured practice: 15 min introductions, 30 min free talk, 15 min feedback.',
    ];
}
