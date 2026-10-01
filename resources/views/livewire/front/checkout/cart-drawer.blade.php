<div class="offcanvas offcanvas-end pb-sm-2 px-sm-2" id="shoppingCart" tabindex="-1" aria-labelledby="shoppingCartLabel" style="width: 500px">

    <!-- Header -->
    <div class="offcanvas-header flex-column align-items-start py-3 pt-lg-4">
        <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-lg-4">
            <h4 class="offcanvas-title" id="shoppingCartLabel">Košarica</h4>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

    </div>

    <!-- Items -->
    <div class="offcanvas-body d-flex flex-column gap-4 pt-2">
        @foreach($cart->get()['items'] as $item)
            <!-- Item -->
            <div class="d-flex align-items-center">
                <a class="position-relative flex-shrink-0" href="shop-product-grocery.html">
                    <!--   <span class="badge text-bg-danger position-absolute top-0 start-0 z-2 mt-0 ms-0">-15%</span>-->
                    <img src="{{ asset($item->attributes['thumb']) }}" width="110" alt="Thumbnail">
                </a>
                <div class="w-100 ps-3">
                    <h5 class="fs-sm fw-medium lh-base mb-2">
                        <a class="hover-effect-underline" href="shop-product-grocery.html">{{ $item->name }}</a>
                    </h5>
                    <div class="h6 pb-1 mb-2">{{ $item->price }}€</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="count-input rounded-pill">
                            <button type="button" class="btn btn-icon btn-sm" data-decrement aria-label="Decrement quantity" wire:click="changeQuantity({{ $item->id }}, -1)">
                                <i class="ci-minus"></i>
                            </button>
                            <input type="number" class="form-control form-control-sm" value="{{ $item->quantity }}" readonly>
                            <button type="button" class="btn btn-icon btn-sm" data-increment aria-label="Increment quantity" wire:click="changeQuantity({{ $item->id }}, 1)">
                                <i class="ci-plus"></i>
                            </button>
                        </div>
                        <button type="button" wire:click="removeFromCart({{ $item->id }})" class="btn-close fs-sm" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-sm" data-bs-title="Remove" aria-label="Obriši iz košarice"></button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div class="offcanvas-header flex-column align-items-start">
        <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-md-4">
            <span class="text-light-emphasis">Ukupno:</span>
            <span class="h6 mb-0">{{ $cart->get()['total'] }} €</span>
        </div>
        <div class="d-flex w-100 gap-3">
            <a class="btn btn-lg btn-secondary w-100 rounded-pill" href="{{ route('kosarica') }}">Pogledajte košaricu</a>
            <a class="btn btn-lg btn-primary w-100 rounded-pill" href="checkout-v1-delivery-1.html">Na naplatu</a>
        </div>
    </div>
</div>
