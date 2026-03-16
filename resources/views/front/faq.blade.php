@extends('front.layouts.app')

@php
    $faqTitle = 'Česta pitanja (FAQ) - Ljekarne PharmAD';
    $faqDescription = 'Odgovori na najčešća pitanja o narudžbama, dostavi, plaćanju i kupnji na web ljekarni Ljekarne PharmAD.';
    $faqUrl = route('faq');
    $faqImage = asset('media/img/cover-ljekarne-pharmad.jpg');

    $faqSchema = [
        '@context' => 'https://schema.org/',
        '@type' => 'FAQPage',
        '@id' => $faqUrl . '#faq',
        'url' => $faqUrl,
        'name' => 'Česta pitanja (FAQ)',
        'description' => $faqDescription,
        'inLanguage' => app()->getLocale(),
        'mainEntity' => $faq->map(function ($item) {
            return [
                '@type' => 'Question',
                'name' => trim(strip_tags($item->title)),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => trim(preg_replace('/\s+/', ' ', strip_tags($item->description))),
                ],
            ];
        })->filter(function ($item) {
            return ! empty($item['name']) && ! empty($item['acceptedAnswer']['text']);
        })->values()->all(),
    ];

    $faqBreadcrumbs = [
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
                'name' => 'Česta pitanja (FAQ)',
                'item' => $faqUrl,
            ],
        ],
    ];
@endphp

@section('title', $faqTitle)
@section('description', $faqDescription)
@section('canonical', $faqUrl)

@push('meta_tags')
    <meta property="og:locale" content="hr_HR" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $faqTitle }}" />
    <meta property="og:description" content="{{ $faqDescription }}" />
    <meta property="og:url" content="{{ $faqUrl }}" />
    <meta property="og:site_name" content="Ljekarne PharmAD" />
    <meta property="og:image" content="{{ $faqImage }}" />
    <meta property="og:image:secure_url" content="{{ $faqImage }}" />
    <meta property="og:image:alt" content="{{ $faqTitle }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $faqTitle }}" />
    <meta name="twitter:description" content="{{ $faqDescription }}" />
    <meta name="twitter:image" content="{{ $faqImage }}" />
@endpush

@section('content')
    <nav class="mb-4 text-center text-lg-start" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>Naslovna</a></li>
            <li class="breadcrumb-item text-nowrap active" aria-current="page">Česta pitanja (FAQ)</li>
        </ol>
    </nav>

    <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
        <h1 class="h2 mb-3 mb-md-0 me-3">Česta pitanja (FAQ)</h1>
    </section>

    <div class="mt-5 mb-5" style="max-width:1240px">
        <div class="accordion accordion-flush" id="accordionFlushExample">
            @foreach ($faq as $fa)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-heading{{ $fa->id }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse{{ $fa->id }}" aria-expanded="false" aria-controls="flush-collapse{{ $fa->id }}">{{ $fa->title }}</button>
                    </h2>
                    <div class="accordion-collapse collapse" id="flush-collapse{{ $fa->id }}" aria-labelledby="flush-heading{{ $fa->id }}" data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">{!! $fa->description !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('js_after')
    <script type="application/ld+json">
        {!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode($faqBreadcrumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush
