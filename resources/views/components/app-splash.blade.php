{{--
    Opening splash. Shown once per browser session, on the first page a visitor opens.
    Hidden by default so it never appears without JavaScript or on later page loads.
    Critical styles are inline so the splash works even when Vite assets are stale/unbuilt.
--}}
<style>
    .app-splash {
        display: none;
    }

    html.app-splash-visible .app-splash {
        align-items: center;
        background: #fff8f0;
        color: #3d2b1f;
        display: flex;
        flex-direction: column;
        font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        gap: 18px;
        inset: 0;
        justify-content: center;
        opacity: 1;
        position: fixed;
        transition: opacity 0.35s ease;
        z-index: 9999;
    }

    html.app-splash-leaving .app-splash {
        opacity: 0;
        pointer-events: none;
    }

    .app-splash__logo {
        animation: app-splash-in 0.55s cubic-bezier(0.2, 0.8, 0.2, 1) both;
        height: 96px;
        width: 138px;
    }

    .app-splash__name {
        animation: app-splash-in 0.55s cubic-bezier(0.2, 0.8, 0.2, 1) 0.08s both;
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1;
    }

    .app-splash__tagline {
        animation: app-splash-in 0.55s cubic-bezier(0.2, 0.8, 0.2, 1) 0.16s both;
        color: rgba(61, 43, 31, 0.6);
        font-size: 14px;
        line-height: 1;
        margin-top: -6px;
    }

    .app-splash__dots {
        display: flex;
        gap: 7px;
        margin-top: 10px;
    }

    .app-splash__dots span {
        animation: app-splash-dot 1s ease-in-out infinite;
        background: #ff8c42;
        border-radius: 9999px;
        height: 7px;
        width: 7px;
    }

    .app-splash__dots span:nth-child(2) {
        animation-delay: 0.15s;
    }

    .app-splash__dots span:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes app-splash-in {
        from {
            opacity: 0;
            transform: translateY(8px) scale(0.96);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    @keyframes app-splash-dot {
        0%,
        80%,
        100% {
            opacity: 0.3;
            transform: scale(0.8);
        }

        40% {
            opacity: 1;
            transform: scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        html.app-splash-visible .app-splash {
            transition: none;
        }

        .app-splash__logo,
        .app-splash__name,
        .app-splash__tagline,
        .app-splash__dots span {
            animation: none;
        }
    }
</style>

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
