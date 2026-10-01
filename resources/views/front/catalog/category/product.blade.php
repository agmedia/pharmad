<div class="col px-2 mb-3 d-flex align-items-stretch " >

    <div class="card product-card shadow pb-2 ">
        @if ($product->special() < $product->price)
            <span class="badge bg-warning badge-shadow">-{{ number_format(floatval(\App\Helpers\Helper::calculateDiscount($product->price, $product->special())), 0) }}%</span>
        @endif
        <a class="card-img-top d-block overflow-hidden text-center" href="{{ url($product->url) }}">
            <img loading="lazy" src="{{ str_replace('.webp','-thumb.webp', $product->image) }}" width="300" height="300" alt="{{ $product->name }}">
        </a>
        <div class="card-body pt-2" style="min-height: 120px;">

            <div class="d-flex flex-wrap justify-content-between align-items-start pb-1">
                <div class="text-muted fs-xs me-1">
                    @if ($product->author)
                    <a class="product-meta fw-medium" href="{{ route('catalog.route.author', ['author' => $product->author]) }}">{{ $product->author->title }}</a>
                    @endif
                </div>

            </div>
            <h3 class="product-title fs-sm text-truncate"><a href="{{ url($product->url) }}">{{ $product->name }}</a></h3>

            @include('front.catalog.partials.prices', ['product' => $product, 'compact' => true])
        </div>
        <div class="product-floating-btn">
            <add-to-cart-btn-simple id="{{ $product->id }}" available="{{ $product->quantity }}"></add-to-cart-btn-simple>
        </div>
    </div>
</div>
