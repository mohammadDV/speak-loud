{{--
    Global wait UI for Livewire requests and SPA navigations.
    Hidden by default; request-loader.js reveals the bar (~150ms) and overlay (~400ms).
    Styles live in resources/css/brand-loaders.css.
--}}
<div
    class="app-request-loader"
    data-app-request-loader
    aria-hidden="true"
>
    <div class="app-request-loader__bar" role="presentation"></div>

    <div
        class="app-request-loader__overlay"
        role="status"
        aria-live="polite"
        aria-atomic="true"
    >
        <img
            class="app-request-loader__logo"
            src="{{ asset('images/logo-mark.webp') }}"
            width="103"
            height="72"
            alt=""
            decoding="async"
        >
        <div class="app-request-loader__dots" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <span class="app-request-loader__sr-only">Loading</span>
    </div>
</div>
