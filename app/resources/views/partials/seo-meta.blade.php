@php
    $resolvedSeo = is_array($seo ?? null) ? $seo : [];
@endphp

@if(filled($resolvedSeo['description'] ?? null))
    <meta name="description" content="{{ $resolvedSeo['description'] }}">
@endif
@if(filled($resolvedSeo['robots'] ?? null))
    <meta name="robots" content="{{ $resolvedSeo['robots'] }}">
@endif
@if(filled($resolvedSeo['author'] ?? null))
    <meta name="author" content="{{ $resolvedSeo['author'] }}">
@endif
@if(filled($resolvedSeo['keywords'] ?? null))
    <meta name="keywords" content="{{ $resolvedSeo['keywords'] }}">
@endif
@if(filled($resolvedSeo['canonical'] ?? null))
    <link rel="canonical" href="{{ $resolvedSeo['canonical'] }}">
@endif
@foreach(($resolvedSeo['alternate_links'] ?? []) as $alternateLink)
    @if(filled($alternateLink['href'] ?? null))
        @php
            $alternateType = filled($alternateLink['type'] ?? null) ? ' type="'.e($alternateLink['type']).'"' : '';
            $alternateTitle = filled($alternateLink['title'] ?? null) ? ' title="'.e($alternateLink['title']).'"' : '';
        @endphp
        <link rel="alternate"{!! $alternateType !!}{!! $alternateTitle !!} href="{{ $alternateLink['href'] }}">
    @endif
@endforeach
@if(filled($resolvedSeo['title'] ?? null))
    <meta property="og:title" content="{{ $resolvedSeo['title'] }}">
    <meta name="twitter:title" content="{{ $resolvedSeo['title'] }}">
@endif
@if(filled($resolvedSeo['description'] ?? null))
    <meta property="og:description" content="{{ $resolvedSeo['description'] }}">
    <meta name="twitter:description" content="{{ $resolvedSeo['description'] }}">
@endif
@if(filled($resolvedSeo['og_type'] ?? null))
    <meta property="og:type" content="{{ $resolvedSeo['og_type'] }}">
@endif
@if(filled($resolvedSeo['url'] ?? null))
    <meta property="og:url" content="{{ $resolvedSeo['url'] }}">
@endif
@if(filled($resolvedSeo['site_name'] ?? null))
    <meta property="og:site_name" content="{{ $resolvedSeo['site_name'] }}">
@endif
<meta property="og:locale" content="zh_CN">
@if(filled($resolvedSeo['twitter_card'] ?? null))
    <meta name="twitter:card" content="{{ $resolvedSeo['twitter_card'] }}">
@endif
@if(filled($resolvedSeo['published_time'] ?? null))
    <meta property="article:published_time" content="{{ $resolvedSeo['published_time'] }}">
@endif
@if(filled($resolvedSeo['modified_time'] ?? null))
    <meta property="article:modified_time" content="{{ $resolvedSeo['modified_time'] }}">
@endif
@foreach(($resolvedSeo['json_ld'] ?? []) as $schema)
    @if(is_array($schema) && $schema !== [])
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
@endforeach
