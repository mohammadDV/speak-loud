<?php

namespace App\Support;

class HomeDemoVideo
{
    /**
     * @return array{type: 'youtube'|'vimeo'|'file', src: string}|null
     */
    public static function embed(?string $url = null): ?array
    {
        $url = trim((string) ($url ?? config('services.home.demo_video_url')));

        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $url, $matches) === 1) {
            return [
                'type' => 'youtube',
                'src' => 'https://www.youtube-nocookie.com/embed/'.$matches[1],
            ];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $matches) === 1) {
            return [
                'type' => 'vimeo',
                'src' => 'https://player.vimeo.com/video/'.$matches[1],
            ];
        }

        if (! self::isAllowedFileUrl($url)) {
            return null;
        }

        return [
            'type' => 'file',
            'src' => $url,
        ];
    }

    private static function isAllowedFileUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
