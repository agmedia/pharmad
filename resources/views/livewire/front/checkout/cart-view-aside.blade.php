<!-- Order summary (sticky sidebar) -->
<aside class="col-lg-4 offset-xl-1" style="margin-top: -100px">
    <div class="position-sticky top-0" style="padding-top: 100px">
        <div class="bg-body-tertiary rounded-5 p-4 mb-3">
            <div class="p-sm-2 p-lg-0 p-xl-2">
                <div class="border-bottom pb-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="mb-0">Sažetak</h5>
                        <div class="nav">
                            <a class="nav-link text-decoration-underline p-0" href="{{ route('kosarica') }}">Uredi</a>
                        </div>
                    </div>
                    <a class="d-flex align-items-center gap-2 text-decoration-none" href="#">
                        @foreach ($items as $item)
                            <div class="ratio ratio-1x1" style="max-width: 64px">
                                <img src="{{ asset($item['attributes']['thumb']) }}" class="d-block rounded p-1" alt="{{ $item['name'] }}">
                            </div>
                        @endforeach
                        <i class="ci-chevron-right text-body fs-xl p-0 ms-auto"></i>
                    </a>
                </div>
                <ul class="list-unstyled fs-sm gap-3 mb-0">
                    <li class="d-flex justify-content-between">
                        Ukupno ({{ $cart['count'] }} artikla):
                        <span class="text-dark-emphasis fw-medium">{{ price($cart['subtotal'], true) }}</span>
                    </li>
                    @foreach ($cart['conditions'] as $condition)
                        @if ($condition->getValue() > 0)
                            <li class="d-flex justify-content-between">
                                {{ $condition->getName() }}:
                                <span class="text-dark-emphasis fw-medium">{{ price($condition->getValue(), true) }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="border-top pt-4 mt-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fs-sm">Sveukupno:</span>
                        <span class="h5 mb-0">{{ price($cart['total'], true) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="accordion bg-body-tertiary rounded-5 p-4">
            <div class="accordion-item border-0">
                <h3 class="accordion-header" id="promoCodeHeading">
                    <button type="button" class="accordion-button animate-underline collapsed py-0 ps-sm-2 ps-lg-0 ps-xl-2" data-bs-toggle="collapse" data-bs-target="#promoCode" aria-expanded="false" aria-controls="promoCode">
                        <i class="ci-percent fs-xl me-2"></i>
                        <span class="animate-target me-2">Kod za popust</span>
                    </button>
                </h3>
                <div class="accordion-collapse collapse" id="promoCode" aria-labelledby="promoCodeHeading">
                    <div class="accordion-body pt-3 pb-2 ps-sm-2 px-lg-0 px-xl-2">
                        <form class="needs-validation d-flex gap-2" novalidate>
                            <div class="position-relative w-100">
                                <input type="text" class="form-control" placeholder="Unesite promo kod" required>
                                <div class="invalid-tooltip bg-transparent py-0">Unesite ispravan promo kod!</div>
                            </div>
                            <button type="submit" class="btn btn-dark">Primjeni</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
