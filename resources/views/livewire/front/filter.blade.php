@if (!empty($categories))
        <div class="widget widget-categories">
            <div class="accordion" id="shop-categories">

                @foreach ($categories as $_group => $_categories)
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header px-grid-gutter {{ (isset($group) && $group == \Illuminate\Support\Str::slug($_group)) ? 'bg-default' : '' }}">
                            <button
                                class="accordion-button collapsed py-3"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#id{{ \Illuminate\Support\Str::slug($_group) }}"
                                aria-expanded="false"
                                aria-controls="id{{ \Illuminate\Support\Str::slug($_group) }}"
                            >
                                <span class="d-flex align-items-center">{{ $_group }}</span>
                            </button>

                            @if (empty($_categories))
                                <a href="{{ route('catalog.route', ['group' => \Illuminate\Support\Str::slug($_group)]) }}"
                                   class="nav-link-style d-block fs-md py-3" role="link">
                                    <span class="d-flex align-items-center"><span></span> {{ $_group }}</span>
                                </a>
                            @endif
                        </h3>

                        <div
                            class="collapse {{ (isset($group) && $group == \Illuminate\Support\Str::slug($_group)) ? 'show' : '' }}"
                            id="id{{ \Illuminate\Support\Str::slug($_group) }}"


                            data-bs-parent="#shop-categories"
                        >
                            <div class="px-grid-gutter pt-1 pb-4">
                                <div class="widget widget-links">
                                    @foreach ($_categories as $category)
                                        @if (!empty($category['url']))
                                            <ul class="widget-list">
                                                <li class="widget-list-item pb-2 {{ (isset($cat) && $cat->id == $category['id']) ? 'active' : '' }}">
                                                    <a class="widget-list-link" href="{{ $category['url'] }}">{{ $category['title'] }}</a>

                                                    @if (!empty($category['subs']))
                                                        <ul class="widget-list pt-2">
                                                            @foreach ($category['subs'] as $subcategory)
                                                                <li class="widget-list-item {{ (isset($subcat) && $subcat->id == $subcategory['id']) ? 'active' : '' }}">
                                                                    <a class="widget-list-link fs-sm" href="{{ $subcategory['url'] }}">{{ $subcategory['title'] }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            </ul>
                                        @endif
                                    @endforeach

                                    <ul class="widget-list mt-2">
                                        <li class="widget-list-item">
                                            <a class="widget-list-link" href="{{ route('catalog.route', ['group' => \Illuminate\Support\Str::slug($_group)]) }}">
                                                Pogledajte sve <i class="fs-xs ci-arrow-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>

     <div class="accordion-item border-bottom">
            <h3 class="accordion-header px-grid-gutter">
                <a class="nav-link-style d-block fs-md py-3" href="{{ route('catalog.route.actions') }}">
                    <span class="d-flex align-items-center"><i class="ci-discount fs-lg mt-n1 me-2"></i>Akcije</span>
                </a>
            </h3>
        </div>

            <div class="accordion-item border-bottom">
                <h3 class="accordion-header px-grid-gutter">
                    <a class="nav-link-style d-block fs-md py-3" href="{{ route('catalog.route.author') }}">
                        <span class="d-flex align-items-center"><i class="ci-heart-circle fs-lg mt-n1 me-2"></i>Brandovi</span>
                    </a>
                </h3>
            </div>

            <div class="accordion-item border-bottom">
                <h3 class="accordion-header px-grid-gutter">
                    <a class="nav-link-style d-block fs-md py-3" href="{{ route('poslovnice') }}">
                        <span class="d-flex align-items-center"><i class="ci-location fs-lg mt-n1 me-2"></i>Naše ljekarne</span>
                    </a>
                </h3>
            </div>

            <div class="accordion-item border-bottom">
                <h3 class="accordion-header px-grid-gutter">
                    <a class="nav-link-style d-block fs-md py-3" href="{{ route('catalog.route.blog') }}">
                        <span class="d-flex align-items-center"><i class="ci-menu-circle fs-lg mt-n1 me-2"></i>Blog</span>
                    </a>
                </h3>
            </div>
        </div>
    @endif
