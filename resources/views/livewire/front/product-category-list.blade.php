<section class="col" id="product-list-top">
    <!-- Toolbar-->
    <div class="d-flex justify-content-center justify-content-sm-between align-items-center pt-2 pb-2  mt-3">
        <div class="d-flex flex-wrap">
            <div class="dropdown me-2 "><a class="btn btn-primary dropdown-toggle collapsed" href="#shop-sidebar" data-bs-toggle="collapse" aria-expanded="false"><i class="ci-filter-alt"></i></a></div>
            <div class="d-flex align-items-center flex-nowrap me-3 me-sm-4 pb-3">
                <label class="text-light opacity-75 text-nowrap fs-sm  d-none d-sm-block" for="sorting"></label>
                <select class="form-select" wire:model="sort" wire:change="selectSortBtn">
                    <option value="">Sortiraj</option>
                    @foreach (config('settings.sorting_list') as $item)
                        <option value="{{ $item['value'] }}" @if(request()->get('sort') == $item['value']) selected @endif>{{ $item['title'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!--  <div class="d-flex pb-3"><a class="nav-link-style nav-link-light me-3" href="#"><i class="ci-arrow-left"></i></a><span class="fs-md text-light">{{ $products->currentPage() }} / {{ $products->lastPage() }}</span><a class="nav-link-style nav-link-light ms-3" href="#"><i class="ci-arrow-right"></i></a></div>-->

        <div class="d-flex pb-3">  <span class="fs-sm text-light btn btn-primary btn-sm text-nowrap ms-2 d-none d-sm-block">Ukupno {{ $products->total() }} artikala</span></div>
    </div>

    <div class="offcanvas offcanvas-start bg-white w-100 rounded-3 shadow-lg py-1"
         tabindex="-1"
         id="shop-sidebar"
         aria-labelledby="shop-sidebar-label"
         style="max-width: 22rem;">
        <div class="offcanvas-header">
            <h5 id="shop-sidebar-label">Filteri</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">

            <!-- Filter by Brand-->
            <div class="widget widget-filter mb-4 pb-4 border-bottom">
                <h3 class="widget-title">Brand</h3>

                <!-- (Opcionalno) Tražilica autora -->

                <ul class="widget-list widget-filter-list list-unstyled pt-1"
                    style="max-height: 12rem;"
                    data-simplebar
                    data-simplebar-auto-hide="false">

                    @forelse($authors as $author)
                        @php
                            $aCount = (int) ($authorCounts[$author->id] ?? 0);
                            $aSelected = in_array($author->id, $selectedAuthors ?? []);
                        @endphp

                        @if($aCount > 0 || $aSelected)
                            <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="author-{{ $author->id }}"
                                           value="{{ $author->id }}"
                                           wire:model="selectedAuthors"
                                           wire:key="author-{{ $author->id }}"
                                           wire:loading.attr="disabled">
                                    <label class="form-check-label widget-filter-item-text" for="author-{{ $author->id }}">
                                        {{ $author->title }}
                                    </label>
                                </div>
                                <span class="fs-xs text-muted">{{ $aCount }}</span>
                            </li>
                        @endif
                    @empty
                        <li class="text-muted fs-sm px-2">Nema brendova u ovoj kategoriji.</li>
                    @endforelse
                </ul>
            </div>

            <!-- Filter by Price -->
            <div class="widget widget-filter mb-4 pb-4 border-bottom">
                <h3 class="widget-title">Cijena</h3>
                <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 12rem;" data-simplebar data-simplebar-auto-hide="false">
                    @php
                        $priceRanges = [
                            '0-10'   => '0 - 10€',
                            '10-20'  => '10 - 20€',
                            '20-30'  => '20 - 30€',
                            '30-40'  => '30 - 40€',
                            '40-50'  => '40 - 50€',
                            '50-100' => '50 - 100€',
                            '100+'   => '100€+',
                        ];
                    @endphp

                    @foreach ($priceRanges as $value => $label)
                        @php
                            $count = (int) ($priceCounts[$value] ?? 0);
                            $isSelected = in_array($value, $selectedPriceRanges ?? []);
                        @endphp

                        @if($count > 0 || $isSelected)
                            <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="price-{{ $value }}"
                                           value="{{ $value }}"
                                           wire:model="selectedPriceRanges"
                                           wire:key="price-range-{{ $value }}"
                                           wire:loading.attr="disabled">
                                    <label class="form-check-label widget-filter-item-text" for="price-{{ $value }}">
                                        {{ $label }}
                                    </label>
                                </div>
                                <span class="fs-xs text-muted">{{ $count }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>

                <!-- Gumb za čišćenje filtera -->
                <div class="mt-3">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary w-100"
                            wire:click="clearFilters">
                        Očisti filtere
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Products grid-->
    <div class=" row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 row-cols-xxxl-6 mb-3 px-2">
        @forelse ($products as $product)
            @include('front.catalog.category.product')
        @empty
    </div>
    <div class="col-md-12 px-2 mb-4">
        @php
            $name = Route::currentRouteName()
        @endphp
        @if ($name == 'pretrazi')

            <h2>Nema rezultata pretrage</h2>

            <p> Vaša pretraga za  <mark>"{{ $searchterm = request()->input('pojam') }}"</mark> pronašla je 0 rezultata.</p>

            <h4 class="h5">Savjeti i smjernica</h4>

            <ul class="list-style">
                <li>Dvaput provjerite pravopis.</li>
                <li>Ograničite pretragu na samo jedan ili dva pojma.</li>
                <li>Budite manje precizni u terminologiji. Koristeći više općenitih termina prije ćete doći do sličnih i povezanih proizvoda.</li>
            </ul>
            <hr class="d-sm-none">

        @elseif ($name == 'catalog.route.actions')

            <h2>Trenutno nema artikala na sniženju</h2>

            <p> Navratite nek drugi put :-)</p>

        @else

            <h2>Trenutno nema proizvoda</h2>

            <p> Pogledajte u nekoj drugoj kategoriji ili probajte sa tražilicom :-)</p>

            <hr class="d-sm-none">

        @endif

        @endforelse
    </div>

    {{ $products->onEachSide(1)->links() }}
</section>

@push('js_after')
    <script>
        (function () {
            function scrollTopHandler(e) {
                const behavior = 'smooth';
                const containerSel = e.detail?.container || null;

                // pričekaj da Livewire dovrši DOM patch
                requestAnimationFrame(() => {
                    // ako je specificiran scroll container
                    if (containerSel) {
                        const c = document.querySelector(containerSel);
                        if (c) {
                            if ('scrollTo' in c) c.scrollTo({ top: 0, left: 0, behavior });
                            else c.scrollTop = 0;
                            return;
                        }
                    }
                    // scrollaj window (i fallback na document elemete)
                    if ('scrollTo' in window) window.scrollTo({ top: 0, left: 0, behavior });
                    document.documentElement.scrollTop = 0;
                    document.body.scrollTop = 0;
                });
            }

            // Livewire v2 emitira na window:
            window.addEventListener('lw-scroll-top', scrollTopHandler);
        })();
    </script>
@endpush
