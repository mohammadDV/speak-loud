<?php

namespace App\Support;

class ScheduleCardGradients
{
    /**
     * @var list<array{from: string, to: string, body: string}>
     */
    private const GRADIENTS = [
        ['from' => '#FF8C42', 'to' => '#FFD166', 'body' => '#FFF0E0'],
        ['from' => '#9B59B6', 'to' => '#6C3483', 'body' => '#F3EAF8'],
        ['from' => '#1ABC9C', 'to' => '#16A085', 'body' => '#E7F7F3'],
        ['from' => '#3498DB', 'to' => '#2980B9', 'body' => '#E8F4FC'],
    ];

    /**
     * @return array{from: string, to: string, body: string}
     */
    public static function forId(int $id): array
    {
        return self::GRADIENTS[$id % count(self::GRADIENTS)];
    }

    /**
     * @return array{from: string, to: string, body: string}
     */
    public static function forIndex(int $index): array
    {
        return self::forId($index);
    }
}
