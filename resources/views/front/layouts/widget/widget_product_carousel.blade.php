<!-- {"title": "Carousel", "description": "Widget za product carousel"} -->
<section class="pt-2 pb-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center pt-1   pb-2 mb-2">
        <div>
            <h2 class="h3 mb-0 pt-3 font-title me-3"><span class="border-color">{{ $data['title'] }}</span></h2>
            @if($data['subtitle'])  <p class=" text-ph fs-md mb-0">{{ $data['subtitle'] }}</p> @endif
        </div>
        @if($data['url'] !='/')
         <a class="btn btn-primary btn-sm btn-shadow mt-3" href="{{ url($data['url']) }}"><span class="d-none d-sm-inline-block">Pogledajte ponudu</span> <i class="ci-arrow-right fs-xs "></i></a>
        @endif

    </div>
    <div class="tns-carousel pt-2 pb-2">
        <div class="tns-carousel-inner" data-carousel-options="{&quot;items&quot;: 2, &quot;gutter&quot;: 15, &quot;controls&quot;: true, &quot;nav&quot;: true, &quot;responsive&quot;: {&quot;0&quot;:{&quot;items&quot;:1},&quot;500&quot;:{&quot;items&quot;:2},&quot;768&quot;:{&quot;items&quot;:2}, &quot;992&quot;:{&quot;items&quot;:5, &quot;gutter&quot;: 30}}}">
            @foreach ($data['items'] as $product)
                <!-- Product-->
                <div>
                    @include('front.catalog.category.product')
                </div>
            @endforeach
        </div>
    </div>


</section>
