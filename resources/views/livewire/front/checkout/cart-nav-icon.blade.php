<div class="dropdown">
    <a href="{{ route('kosarica') }}" type="button" class="btn btn-icon fs-xl btn-outline-secondary position-relative border-0 rounded-circle animate-scale " data-bs-toggle="dropdown" data-bs-trigger="hover" aria-haspopup="true" aria-expanded="false">
        <span id="cart-header-count" class="position-absolute top-0 start-100 badge fs-xs text-bg-primary rounded-pill ms-n3 z-2" style="--cz-badge-padding-y: .25em; --cz-badge-padding-x: .42em">{{ $count }}</span>
        <i class="ci-shopping-cart animate-target"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        <div class="widget widget-cart px-3 pt-2 pb-3" style="width: 20rem;">
            @forelse ($cart->get()['items'] as $item)
                <div class="widget-cart-item pb-2 border-bottom">
                    <div class="d-flex align-items-center">
                        <button type="button" aria-label="Remove" wire:click="removeFromCart({{ $item->id }})" class="btn-close text-danger me-2"></button>
                        <a href="#" class="d-block flex-shrink-0 pt-2">
                            <img src="{{ $item->attributes->thumb }}" alt="{{ $item->name }}" title="{{ $item->name }}" style="width: 5rem;"></a>
                        <div class="ps-2">
                            <h6 class="widget-product-title fs-sm">
                                <a href="#">{{ $item->name }}</a>
                            </h6>
                            <div class="widget-product-meta"><span class="text-primary me-2">{{ $item->price }} €</span><span class="text-muted">x {{ $item->quantity }}</span></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="widget-cart-item pb-2 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="text-primary fs-base ms-1">Nažalost vaša košarica je prazna.</span>
                    </div>
                </div>
            @endforelse
        </div>
        <div class="d-flex flex-wrap justify-content-between px-3 align-items-center py-3">
            <div class="fs-sm me-2 py-2">
                <span class="text-muted">Ukupno:</span>
                <span class="text-primary fs-base ms-1">{{ $cart->get()['subtotal'] }} €</span>
            </div>
        </div>
        <a href="{{ route('kosarica') }}" class="btn btn-primary btn-sm d-block px-3 w-100"><i class="ci-card me-2 fs-base align-middle"></i>Dovrši kupnju</a>
    </div>
</div>
