<div class="row">
    <!-- Items list -->
    <div class="col-lg-8">
        <div class="pe-lg-2 pe-xl-3 me-xl-3">
            <p class="fs-sm">Na sljedećem koraku birate <span class="text-dark-emphasis fw-semibold">termin montaže</span> ili vrstu dostave</p>
            <div class="progress w-100 overflow-visible mb-4" role="progressbar" aria-label="Free shipping progress" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="height: 4px">
                <div class="progress-bar bg-warning rounded-pill position-relative overflow-visible" style="width: 100%; height: 4px">
                    <div class="position-absolute top-50 end-0 d-flex align-items-center justify-content-center translate-middle-y bg-body border border-warning rounded-circle me-n1" style="width: 1.5rem; height: 1.5rem">
                        <i class="ci-star-filled text-warning"></i>
                    </div>
                </div>
            </div>

            <!-- Table of items -->
            <table class="table position-relative z-2 mb-4">
                <thead>
                <tr>
                    <th scope="col" class="fs-sm py-3 ps-0"></th>
                    <th scope="col" class="fs-sm fw-normal py-3 ps-0"><span class="text-body">Artikl</span></th>
                    <th scope="col" class="text-body fs-sm fw-normal py-3 d-none d-xl-table-cell"><span class="text-body">Cijena</span></th>
                    <th scope="col" class="text-body fs-sm fw-normal py-3 d-none d-md-table-cell"><span class="text-body">Količina</span></th>
                    <th scope="col" class="text-body fs-sm fw-normal py-3 d-none d-md-table-cell"><span class="text-body">Rabat</span></th>
                    <th scope="col" class="text-body fs-sm fw-normal py-3 d-none d-md-table-cell"><span class="text-body">Ukupno</span></th>
                </tr>
                </thead>
                <tbody class="align-middle">

                <!-- Item -->
                @forelse ($items as $item)
                    <tr>
                        <td class="text-end py-3 px-0">
                            <button type="button" class="btn-close fs-sm" wire:click="removeItemFromCart({{ $item['id'] }})" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-sm" data-bs-title="Remove" aria-label="Remove from cart"></button>
                        </td>
                        <td class="py-3 ps-0">
                            <div class="d-flex align-items-center">
                                <a class="flex-shrink-0" href="{{ $item->attributes['path'] }}">
                                    <img src="{{ asset($item->attributes['thumb']) }}" width="110" alt="{{ $item['name'] }}">
                                </a>
                                <div class="w-100 min-w-0 ps-2 ps-xl-3">
                                    <h5 class="d-flex animate-underline mb-2">
                                        <a class="d-block fs-sm fw-medium animate-target" href="{{ $item->attributes['path'] }}">{{ $item['name'] }}</a>
                                    </h5>
                                    @if ($item->attributes->action)
                                        <span class="fs-sm fw-light">{{ $item->attributes->action['title'] }} {{ $item->attributes->action['discount'] }}</span>
                                    @endif

                                    <div class="count-input rounded-2 d-md-none mt-3">
                                        <button type="button" wire:click="changeItemQuantity({{ $item['id'] }}, -1)" class="btn btn-sm btn-icon" data-decrement aria-label="Decrement quantity">
                                            <i class="ci-minus"></i>
                                        </button>
                                        <input type="number" class="form-control form-control-sm" value="{{ $item['quantity'] }}" min="1" max="{{ $item->attributes['available_quantity'] }}" readonly>
                                        <button type="button" wire:click="changeItemQuantity('{{ $item['id'] }}', 1)" class="btn btn-sm btn-icon" data-increment aria-label="Increment quantity">
                                            <i class="ci-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="h6 py-3 d-none d-xl-table-cell fs-sm">{{ price($item['price'], true) }}</td>
                        <td class="py-3 d-none d-md-table-cell">
                            <div class="count-input">
                                <button type="button" wire:click="changeItemQuantity({{ $item['id'] }}, -1)" class="btn btn-icon btn-sm" data-decrement aria-label="Decrement quantity">
                                    <i class="ci-minus"></i>
                                </button>
                                <input type="number" class="form-control form-control-sm" value="{{ $item['quantity'] }}" min="1" max="{{ $item->attributes['available_quantity'] }}" readonly>
                                <button type="button" wire:click="changeItemQuantity('{{ $item['id'] }}', 1)" class="btn btn-icon btn-sm" data-increment aria-label="Increment quantity">
                                    <i class="ci-plus"></i>
                                </button>
                            </div>
                        </td>
                        @if ($item->hasConditions())
                            <td class="py-3 d-none d-md-table-cell">{{ $item->conditions->getValue() }}</td>
                        @else
                            <td class="py-3 d-none d-md-table-cell"></td>
                        @endif
                        <td class="h6 py-3 d-none d-md-table-cell fs-sm">{{ price($item->getPriceSumWithConditions(), true) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-4 ps-0 text-center" colspan="6">
                            <h4>Nažalost nemate ništa u košarici.</h4>
                        </td>
                    </tr>
                @endforelse

                </tbody>
            </table>

            <div class="nav position-relative z-2 mb-4 mb-lg-0">
                <a class="nav-link animate-underline px-0" href="{{ route('catalog.route', ['group' => group(true)]) }}">
                    <i class="ci-chevron-left fs-lg me-1"></i>
                    <span class="animate-target">Nastavi kupnju</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Order summary (sticky sidebar) -->
    <aside class="col-lg-4" style="margin-top: -100px">
        <div class="position-sticky top-0" style="padding-top: 100px">
            <div class="bg-body-tertiary rounded-5 p-4 mb-3">
                <div class="p-sm-2 p-lg-0 p-xl-2">
                    <h5 class="border-bottom pb-4 mb-4">Sažetak</h5>
                    <ul class="list-unstyled fs-sm gap-3 mb-0">
                        <li class="d-flex justify-content-between">
                            Ukupno ({{ $cart['count'] }} artikla):
                            <span class="text-dark-emphasis fw-medium">{{ price($cart['subtotal'], true) }}</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            Dostava:
                            <span class="text-dark-emphasis fw-medium">Obračun na sljedećem koraku</span>
                        </li>
                    </ul>
                    <div class="border-top pt-4 mt-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fs-sm">Sveukupno:</span>
                            <span class="h5 mb-0">{{ price($cart['total'], true) }}</span>
                        </div>
                        <a class="btn btn-lg btn-primary w-100" href="{{ route('dostava') }}">
                            Dovrši kupnju
                            <i class="ci-chevron-right fs-lg ms-1 me-n1"></i>
                        </a>
                        @if (auth()->guest())
                            <div class="nav justify-content-center fs-sm mt-3">
                                <a class="nav-link text-decoration-underline p-0 me-1" href="#authForm" data-bs-toggle="offcanvas" role="button">Prijava ili Registracija</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @include('front.checkout.partials.coupon')
        </div>
    </aside>
</div>
