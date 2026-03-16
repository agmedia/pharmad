@extends('front.layouts.app')

@php
    $pageTitle = 'Naše ljekarne - Ljekarne PharmAD';
    $pageDescription = 'Adrese, kontakti i lokacije svih poslovnica Ljekarni PharmAD.';
    $pageUrl = route('poslovnice');
    $pageImage = asset('media/img/cover-ljekarne-pharmad.jpg');

    $locations = [
        [
            'id' => 'prigorje-brdovecko',
            'code' => '001',
            'name' => 'Prigorje Brdovečko',
            'street' => 'Zagrebačka 99',
            'postal_code' => '10291',
            'city' => 'Prigorje Brdovečko',
            'phone_display' => '01/ 46 78 893',
            'phone_href' => 'tel:0038514678893',
            'phone_schema' => '+38514678893',
            'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2777.5985198582816!2d15.725572116226028!3d45.87934141366611!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765c8980502a3ab%3A0x400285e992bfd8c6!2sLjekarne%20PharmAD%20Prigorje%20Brdove%C4%8Dko!5e0!3m2!1shr!2shr!4v1612183287277!5m2!1shr!2shr',
        ],
        [
            'id' => 'senkovec',
            'code' => '002',
            'name' => 'Šenkovec',
            'street' => 'Zagrebačka 61',
            'postal_code' => '10292',
            'city' => 'Šenkovec',
            'phone_display' => '01/ 33 96 511',
            'phone_href' => 'tel:0038513396511',
            'phone_schema' => '+38513396511',
            'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2777.056463104886!2d15.687339716226285!3d45.890183812937586!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765c976513b871d%3A0xf606c67a5b8b81cd!2sLjekarne%20PharmAD%20Harmica!5e0!3m2!1shr!2shr!4v1612183764864!5m2!1shr!2shr',
        ],
        [
            'id' => 'kraljevec-na-sutli',
            'code' => '003',
            'name' => 'Kraljevec na Sutli',
            'street' => 'Kraljevec na Sutli 131',
            'postal_code' => '49294',
            'city' => 'Kraljevec na Sutli',
            'phone_display' => '049/ 289 004',
            'phone_href' => 'tel:0038549289004',
            'phone_schema' => '+38549289004',
            'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5544.3310568376555!2d15.721483280024986!3d45.987924170952965!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765c1a168a3bda3%3A0xc5c7851a4bd22923!2sLjekarne%20PharmAD%20Kraljevec%20na%20Sutli!5e0!3m2!1shr!2shr!4v1612183797443!5m2!1shr!2shr',
        ],
        [
            'id' => 'zabok',
            'code' => '004',
            'name' => 'Zabok',
            'street' => 'Dubrava Zabočka 90B',
            'postal_code' => '49210',
            'city' => 'Zabok',
            'phone_display' => '049/ 220 000',
            'phone_href' => 'tel:0038549220000',
            'phone_schema' => '+38549220000',
            'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2770.4657700012563!2d15.949684816228503!3d46.021854404079356!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765e7fd5b127113%3A0xac81f85e634bf134!2sLjekarne%20PharmAD%20Zabok!5e0!3m2!1shr!2shr!4v1612183825241!5m2!1shr!2shr',
        ],
    ];

    $locationListSchema = [
        '@context' => 'https://schema.org/',
        '@type' => 'ItemList',
        '@id' => $pageUrl . '#locations',
        'name' => 'Ljekarne PharmAD poslovnice',
        'itemListElement' => collect($locations)->values()->map(function ($location, $index) use ($pageUrl) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => 'Ljekarne PharmAD ' . $location['name'],
                'url' => $pageUrl . '#' . $location['id'],
            ];
        })->all(),
    ];

    $pharmacySchemas = collect($locations)->map(function ($location) use ($pageUrl, $pageImage) {
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Pharmacy',
            '@id' => $pageUrl . '#' . $location['id'],
            'name' => 'Ljekarne PharmAD ' . $location['name'],
            'url' => $pageUrl . '#' . $location['id'],
            'image' => $pageImage,
            'branchCode' => $location['code'],
            'telephone' => $location['phone_schema'],
            'email' => 'webshop@ljekarne-pharmad.hr',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $location['street'],
                'postalCode' => $location['postal_code'],
                'addressLocality' => $location['city'],
                'addressCountry' => 'HR',
            ],
            'hasMap' => $location['map_embed'],
            'parentOrganization' => [
                '@id' => url('/') . '#organization',
            ],
            'availableLanguage' => ['hr'],
            'currenciesAccepted' => 'EUR',
        ];
    })->all();

    $locationBreadcrumbs = [
        '@context' => 'https://schema.org/',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Naslovna',
                'item' => route('index'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Naše ljekarne',
                'item' => $pageUrl,
            ],
        ],
    ];
@endphp

@section('title', $pageTitle)
@section('description', $pageDescription)
@section('canonical', $pageUrl)

@push('meta_tags')
    <meta property="og:locale" content="hr_HR" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $pageTitle }}" />
    <meta property="og:description" content="{{ $pageDescription }}" />
    <meta property="og:url" content="{{ $pageUrl }}" />
    <meta property="og:site_name" content="Ljekarne PharmAD" />
    <meta property="og:image" content="{{ $pageImage }}" />
    <meta property="og:image:secure_url" content="{{ $pageImage }}" />
    <meta property="og:image:alt" content="{{ $pageTitle }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $pageTitle }}" />
    <meta name="twitter:description" content="{{ $pageDescription }}" />
    <meta name="twitter:image" content="{{ $pageImage }}" />
@endpush

@section('content')
    <nav class="mb-4 text-center text-lg-start" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>Naslovna</a></li>
            <li class="breadcrumb-item text-nowrap active" aria-current="page">Naše ljekarne</li>
        </ol>
    </nav>

    <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
        <h1 class="h2 mb-3 mb-md-0 me-3">Naše ljekarne</h1>
    </section>

    <section class="pt-grid-gutter">
        <div class="row">
            <div class="col-12 mb-5">
                @foreach ($locations as $location)
                    <article id="{{ $location['id'] }}" class="mb-5">
                        <h4>{{ $location['code'] }} {{ $location['name'] }}</h4>

                        <p>{{ $location['street'] }}, {{ $location['postal_code'] }} {{ $location['city'] }}<br />
                            <strong>TEL:</strong> <a href="{{ $location['phone_href'] }}">{{ $location['phone_display'] }}</a></p>
                        <iframe allowfullscreen="" aria-hidden="false" frameborder="0" height="450" src="{{ $location['map_embed'] }}" style="border:0;" tabindex="0" width="100%"></iframe>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('js_after')
    <script type="application/ld+json">
        {!! json_encode(\App\Helpers\Metatags::webPageSchema($pageTitle, $pageDescription, $pageUrl, 'CollectionPage', $pageImage), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode($locationListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode($locationBreadcrumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    @foreach ($pharmacySchemas as $pharmacySchema)
        <script type="application/ld+json">
            {!! json_encode($pharmacySchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endforeach
@endpush
