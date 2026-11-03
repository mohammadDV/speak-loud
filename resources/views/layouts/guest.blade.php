<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo-head :title="$title ?? null" />
    @stack('seo-extra')
    <script>
        (function () {
            try {
                var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
                var maxAge = 60 * 60 * 24 * 365;
                var secure = location.protocol === 'https:' ? ';Secure' : '';
                document.cookie = 'viewer_timezone=' + encodeURIComponent(tz)
                    + ';path=/;max-age=' + maxAge + ';SameSite=Lax' + secure;
            } catch (e) {
                var secureFallback = location.protocol === 'https:' ? ';Secure' : '';
                document.cookie = 'viewer_timezone=UTC;path=/;max-age=31536000;SameSite=Lax' + secureFallback;
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased">
    <flux:main>
        {{ $slot }}
    </flux:main>
</body>
</html>
