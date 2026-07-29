@php
    $cfg = config('seo');
    // Overrides por página enviados desde el controlador como prop `seo`
    $pageSeo = data_get($page ?? [], 'props.seo', []);
    if (!is_array($pageSeo)) { $pageSeo = []; }

    $title       = $pageSeo['title']       ?? $cfg['title'];
    $description = $pageSeo['description']  ?? $cfg['description'];
    $keywords    = $pageSeo['keywords']     ?? $cfg['keywords'];
    $ogType      = $pageSeo['type']         ?? 'website';
    $noindex     = (bool)($pageSeo['noindex'] ?? false);

    $rawImage    = $pageSeo['image'] ?? $cfg['image'];
    $image       = \Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://'])
                    ? $rawImage : url($rawImage);

    $path        = request()->path();
    $canonical   = $pageSeo['canonical'] ?? ($path === '/' ? url('/') : url($path));

    // Nodos JSON-LD base
    $graph = [
        [
            '@type' => 'Organization',
            '@id'   => url('/#organization'),
            'name'  => $cfg['site_name'],
            'url'   => url('/'),
            'logo'  => url($cfg['icon']),
            'sameAs' => [],
        ],
        [
            '@type' => 'WebSite',
            '@id'   => url('/#website'),
            'name'  => $cfg['site_name'],
            'url'   => url('/'),
            'inLanguage' => 'es',
            'publisher' => ['@id' => url('/#organization')],
        ],
        [
            '@type' => 'WebApplication',
            '@id'   => url('/#app'),
            'name'  => $cfg['site_name'],
            'url'   => url('/'),
            'description' => $cfg['description'],
            'applicationCategory' => 'SportsApplication',
            'operatingSystem' => 'Web, Android, iOS',
            'inLanguage' => 'es',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
        ],
    ];

    // Nodos JSON-LD extra por página (ej. Event de un torneo, FAQPage, etc.)
    if (!empty($pageSeo['jsonld']) && is_array($pageSeo['jsonld'])) {
        foreach ($pageSeo['jsonld'] as $node) {
            $graph[] = $node;
        }
    }

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ];
@endphp
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="author" content="{{ $cfg['author'] }}">
    <meta name="application-name" content="{{ $cfg['site_name'] }}">
    <meta name="theme-color" content="{{ $cfg['theme_color'] }}">
    @if($noindex)
    <meta name="robots" content="noindex, nofollow">
    @else
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    @endif
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Favicons de marca --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="mask-icon" href="/favicon-32.png" color="{{ $cfg['theme_color'] }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $cfg['site_name'] }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:width" content="{{ $cfg['image_width'] }}">
    <meta property="og:image:height" content="{{ $cfg['image_height'] }}">
    <meta property="og:locale" content="{{ $cfg['locale'] }}">
    @foreach(($cfg['locales'] ?? []) as $alt)
        @if($alt !== $cfg['locale'])
    <meta property="og:locale:alternate" content="{{ $alt }}">
        @endif
    @endforeach

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $cfg['twitter_card'] }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">
    <meta name="twitter:site" content="{{ $cfg['twitter_handle'] }}">

    {{-- Structured data (SEO + GEO / posicionamiento en LLMs) --}}
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
