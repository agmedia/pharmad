<div>
    <div class="sidebar-nav tab-pane fade show active" id="categories" role="tabpanel">
        @if ($categories)
            <div class="widget widget-categories">
                <div class="accordion" id="shop-categories">

                    @foreach ($categories as $_group => $_categories)
                        <div class="accordion-item border-bottom">
                            <h3 class="accordion-header px-grid-gutter @if(isset($group) && $group == \Illuminate\Support\Str::slug($_group)) bg-default @endif">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#id{{ \Illuminate\Support\Str::slug($_group) }}" aria-expanded="false" :aria-controls="id{{ \Illuminate\Support\Str::slug($_group) }}">
                                    <span class="d-flex align-items-center"> {{ $_group }}</span>
                                </button>
                                @if (empty($_categories))
                                    <a href="{{ route('catalog.route', ['group' => \Illuminate\Support\Str::slug($_group)]) }}" class="nav-link-style d-block fs-md py-3" role="link">
                                        <span class="d-flex align-items-center"><span></span> {{ $_group }}</span>
                                    </a>
                                @endif
                            </h3>

                            <div class="collapse @if(isset($group) && $group == \Illuminate\Support\Str::slug($_group)) show @endif" id="id{{ \Illuminate\Support\Str::slug($_group) }}" data-bs-parent="#shop-categories">
                                <div class="px-grid-gutter pt-1 pb-4">
                                    <div class="widget widget-links">
                                        @foreach ($_categories as $category)
                                            @if (isset($category['url']))
                                                <ul class="widget-list">
                                                    <li class="widget-list-item pb-1 @if(isset($cat) && $cat->id == $category['id']) active @endif">
                                                        <a class="widget-list-link" href="{{ $category['url'] }}">{{ $category['title'] }} </a>
                                                        <ul class="widget-list pt-1">
                                                            <li class="widget-list-item"><a class="widget-list-link" href="#">Baguette</a></li>
                                                            <li class="widget-list-item"><a class="widget-list-link" href="#">Loaves</a></li>

                                                        </ul>
                                                    </li>
                                                </ul>
                                            @endif
                                        @endforeach
                                        <ul class="widget-list mt-2">
                                            <li class="widget-list-item"><a class="widget-list-link" href="{{ route('catalog.route', ['group' => \Illuminate\Support\Str::slug($_group)]) }}">Pogledajte sve</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>


                    @endforeach
                </div>

                <div class="accordion-item border-bottom">
                    <h3 class="accordion-header px-grid-gutter">
                        <a class="nav-link-style d-block fs-md py-3" href="{{ route('catalog.route.author') }}">
                            <span class="d-flex align-items-center"><i class="ci-heart-circle  fs-lg mt-n1 me-2"></i>Brandovi</span>
                        </a>
                    </h3>
                </div>
                <div class="accordion-item border-bottom">
                    <h3 class="accordion-header px-grid-gutter">
                        <a class="nav-link-style d-block fs-md py-3" href="{{ route('catalog.route.blog') }}">
                            <span class="d-flex align-items-center"><i class="ci-list  fs-lg mt-n1 me-2"></i>Blog</span>
                        </a>
                    </h3>
                </div>

            </div>
        @endif
    </div>
</div>
