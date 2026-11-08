@php
    $gtmId = config('services.google_tag_manager.id');
@endphp

@if (filled($gtmId))
    <script async src="https://www.googletagmanager.com/gtm.js?id={{ $gtmId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
    </script>
@endif
