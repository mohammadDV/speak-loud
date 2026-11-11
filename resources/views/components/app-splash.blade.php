{{--
    Opening splash. Shown once per browser session, on the first page a visitor opens.
    Hidden by default so it never appears without JavaScript or on later page loads.
    Styles live in resources/css/brand-loaders.css.
--}}
<script>
    (function () {
        var storageKey = 'slm-splash-seen';
        var root = document.documentElement;

        function clearSplashClasses() {
            root.classList.remove('app-splash-visible', 'app-splash-leaving');
        }

        // SPA navigations replace <body> but can leave splash classes on <html>.
        // Register once so repeated body swaps do not stack listeners.
        if (!window.__slmSplashNavBound) {
            window.__slmSplashNavBound = true;
            document.addEventListener('livewire:navigating', clearSplashClasses);
            document.addEventListener('livewire:navigated', clearSplashClasses);
        }

        try {
            if (sessionStorage.getItem(storageKey)) {
                return;
            }

            sessionStorage.setItem(storageKey, '1');
        } catch (e) {
            return;
        }

        var minimumMs = 900;
        var maximumMs = 4000;
        var fadeMs = 350;
        var shownAt = Date.now();
        var isHiding = false;

        root.classList.add('app-splash-visible');

        function hideSplash() {
            if (isHiding) {
                return;
            }

            isHiding = true;

            setTimeout(function () {
                root.classList.add('app-splash-leaving');

                setTimeout(function () {
                    clearSplashClasses();
                }, fadeMs);
            }, Math.max(0, minimumMs - (Date.now() - shownAt)));
        }

        if (document.readyState === 'complete') {
            hideSplash();
        } else {
            window.addEventListener('load', hideSplash, { once: true });
        }

        setTimeout(hideSplash, maximumMs);
    })();
</script>

<div class="app-splash" aria-hidden="true">
    <img
        class="app-splash__logo"
        src="{{ asset('images/logo-mark.webp') }}"
        width="138"
        height="96"
        alt=""
        fetchpriority="high"
    >
    <div class="app-splash__name">SpeakLoudMate</div>
    <div class="app-splash__tagline">Practice languages with real people</div>
    <div class="app-splash__dots"><span></span><span></span><span></span></div>
</div>
