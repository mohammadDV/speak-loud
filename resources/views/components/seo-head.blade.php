@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'url' => null,
    'type' => null,
    'robots' => null,
])

@php
    use App\Support\Seo;

    $resolvedTitle = Seo::pageTitle($title ?? ($seoTitle ?? null));
    $resolvedDescription = $description ?? ($seoDescription ?? Seo::defaultDescription());
    $resolvedImage = $image ?? ($seoImage ?? Seo::defaultImage());
    $resolvedImageAlt = $seoImageAlt ?? Seo::SITE_NAME;
    $resolvedImageWidth = $seoImageWidth ?? Seo::defaultImageWidth();
    $resolvedImageHeight = $seoImageHeight ?? Seo::defaultImageHeight();
    $resolvedUrl = $url ?? ($seoUrl ?? url()->current());
    $resolvedType = $type ?? ($seoType ?? 'website');
    $resolvedRobots = $robots ?? ($seoRobots ?? null);
    $resolvedPublishedTime = $seoPublishedTime ?? null;
    $resolvedModifiedTime = $seoModifiedTime ?? null;
    $resolvedAuthor = $seoAuthor ?? null;
    $resolvedJsonLd = $seoJsonLd ?? Seo::defaultJsonLd();
    $resolvedLocale = str_replace('-', '_', app()->getLocale());
    $twitterSite = Seo::twitterSite();
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
@if ($resolvedRobots)
    <meta name="robots" content="{{ $resolvedRobots }}">
@endif
<link rel="canonical" href="{{ $resolvedUrl }}">

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('images/favicon-32.png') }}" sizes="32x32">
<link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">

<meta property="og:site_name" content="{{ Seo::SITE_NAME }}">
<meta property="og:locale" content="{{ $resolvedLocale }}">
<meta property="og:type" content="{{ $resolvedType }}">
<meta property="og:title" content="{{ $resolvedTitle }}">
<meta property="og:description" content="{{ $resolvedDescription }}">
<meta property="og:url" content="{{ $resolvedUrl }}">
<meta property="og:image" content="{{ $resolvedImage }}">
<meta property="og:image:alt" content="{{ $resolvedImageAlt }}">
@if ($resolvedImageWidth)
    <meta property="og:image:width" content="{{ $resolvedImageWidth }}">
@endif
@if ($resolvedImageHeight)
    <meta property="og:image:height" content="{{ $resolvedImageHeight }}">
@endif
@if ($resolvedType === 'article')
    @if ($resolvedPublishedTime)
        <meta property="article:published_time" content="{{ $resolvedPublishedTime }}">
    @endif
    @if ($resolvedModifiedTime)
        <meta property="article:modified_time" content="{{ $resolvedModifiedTime }}">
    @endif
    @if ($resolvedAuthor)
        <meta property="article:author" content="{{ $resolvedAuthor }}">
    @endif
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $resolvedTitle }}">
<meta name="twitter:description" content="{{ $resolvedDescription }}">
<meta name="twitter:image" content="{{ $resolvedImage }}">
@if ($twitterSite)
    <meta name="twitter:site" content="{{ $twitterSite }}">
@endif

@foreach ($resolvedJsonLd as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
