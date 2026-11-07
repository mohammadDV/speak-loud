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
