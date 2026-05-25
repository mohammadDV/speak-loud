@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'url' => null,
    'type' => 'website',
    'robots' => null,
])

@php
    use App\Support\Seo;

    $resolvedTitle = Seo::pageTitle($title ?? ($seoTitle ?? null));
    $resolvedDescription = $description ?? ($seoDescription ?? Seo::defaultDescription());
    $resolvedImage = $image ?? ($seoImage ?? Seo::defaultImage());
    $resolvedUrl = $url ?? ($seoUrl ?? url()->current());
    $resolvedType = $type ?? ($seoType ?? 'website');
    $resolvedRobots = $robots ?? ($seoRobots ?? null);
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
@if ($resolvedRobots)
    <meta name="robots" content="{{ $resolvedRobots }}">
@endif
<link rel="canonical" href="{{ $resolvedUrl }}">

<meta property="og:site_name" content="{{ Seo::SITE_NAME }}">
<meta property="og:type" content="{{ $resolvedType }}">
<meta property="og:title" content="{{ $resolvedTitle }}">
<meta property="og:description" content="{{ $resolvedDescription }}">
<meta property="og:url" content="{{ $resolvedUrl }}">
<meta property="og:image" content="{{ $resolvedImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $resolvedTitle }}">
<meta name="twitter:description" content="{{ $resolvedDescription }}">
<meta name="twitter:image" content="{{ $resolvedImage }}">
