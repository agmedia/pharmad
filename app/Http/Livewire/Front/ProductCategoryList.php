<?php

namespace App\Http\Livewire\Front;

use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCategoryList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $muteScroll = false;

    public $ids = null;
    public $group = null;
    public $cat = null;
    public $subcat = null;

    // URL parametri po slug-u (opcionalno)
    public $author = null;
    public $publisher = null;

    // --- AUTORI (jedini izvor istine za filter) ---
    public $selectedAuthors = [];   // npr. ["74","84"] ili [74,84]
    public $authorSearch = '';

    // --- CIJENE ---
    public $selectedPriceRanges = [];

    // ostalo
    public $sort;
    protected $listeners = ['idChanged'];

    // Ako želiš sort u URL-u
    protected $queryString = [
        'sort' => ['except' => ''],
    ];

    // Opcionalne granice godina
    protected $start;
    protected $end;

    // Publishers ostavljam kako su ti bili, ali nisu bitni za ovaj fix
    protected $publishers = [];

    public function updatingSort()
    {
        $this->muteScroll = true;
        $this->resetPage();
    }

    public function selectSortBtn()
    {
        $this->muteScroll = true;
        $this->resetPage();
    }

    public function updatedPage($page)
    {
        if ($this->muteScroll) {
            $this->muteScroll = false;
            return;
        }

        $this->dispatchBrowserEvent('lw-scroll-top', [
            'to' => 'product-list-top',
            'offset' => 100,
        ]);
    }

    public function updatingSelectedAuthors()
    {
        $this->muteScroll = true; // da ti ne skrolne gore zbog resetPage side-effecta
        $this->resetPage();       // vrati na stranicu 1
    }

    public function updatingSelectedPriceRanges()
    {
        $this->muteScroll = true;
        $this->resetPage();
    }

    public function idChanged($data)
    {
        $this->start = $data['start'] ?? $this->start;
        $this->end   = $data['end']   ?? $this->end;
    }

    public function clearFilters()
    {
        $this->selectedAuthors = [];
        $this->authorSearch = '';
        $this->selectedPriceRanges = [];

        // Ako želiš resetirati i ostale filtere:
        // $this->publishers = [];
        // $this->start = null;
        // $this->end = null;
        // $this->sort = null;

        $this->resetPage(); // vrati na prvu stranicu
    }

    public function render()
    {
        // 1) Normaliziraj AUTORE (iz URL-a i iz Livewire state-a)
        if (request()->has('autor') && empty($this->selectedAuthors)) {
            $aut = request()->input('autor');
            $slugs = is_string($aut) ? explode(',', $aut) : (array) $aut;
            $idsFromSlugs = Author::whereIn('slug', array_filter($slugs))->pluck('id')->all();
            $this->selectedAuthors = array_values(array_unique(array_merge(
                array_map('intval', (array) $this->selectedAuthors),
                array_map('intval', $idsFromSlugs)
            )));
        }

        if ($this->author) {
            if ($a = Author::where('slug', $this->author)->first()) {
                $this->selectedAuthors[] = (int) $a->id;
                $this->selectedAuthors   = array_values(array_unique($this->selectedAuthors));
            }
        }

        $authorIdsFilter = collect($this->selectedAuthors)
            ->map(fn($v) => (int) $v)->filter()->unique()->values()->all();

        // 2) Ostali URL parametri (publisher, godine…)
        if ($this->publisher) {
            $this->publishers[] = $this->publisher;
        }
        if (!$this->start && request()->has('start')) $this->start = request()->input('start');
        if (!$this->end   && request()->has('end'))   $this->end   = request()->input('end');

        // 3) Sastavi $request_data za GLAVNI upit (s odabranim autorima i cijenama)
        $request_data = [];
        if ($this->group)   $request_data['group'] = $this->group;
        if ($this->cat)     $request_data['cat'] = $this->cat;
        if ($this->subcat)  $request_data['subcat'] = $this->subcat;
        if (!empty($authorIdsFilter)) $request_data['autor'] = $authorIdsFilter; // array ID-eva
        if (!empty($this->publishers)) $request_data['nakladnik'] = $this->publishers;
        if ($this->start && strlen($this->start) == 4) $request_data['start'] = $this->start;
        if ($this->end   && strlen($this->end) == 4)   $request_data['end']   = $this->end;
        if ($this->sort) $request_data['sort'] = $this->sort;
        if (!empty($this->selectedPriceRanges)) $request_data['price_ranges'] = $this->selectedPriceRanges;

        $request = new Request($request_data);

        // 4) Normaliziraj $ids
        if (is_string($this->ids)) $this->ids = json_decode($this->ids, true);
        if (is_array($this->ids))  $this->ids = collect($this->ids);

        // 5) Glavni rezultat proizvoda
        $products = (new Product())
            ->filter($request, $this->ids)
            ->with('author')
            ->paginate(config('settings.pagination.front'))
            ->withQueryString();

        /**
         * 6) FACETS s AND logikom:
         *    - $baseAuthors: SVE ostalo uključeno (npr. cijena), samo BEZ autora -> brojači autora ovise o cijeni
         *    - $basePrices:  SVE ostalo uključeno (npr. autor), samo BEZ cijene -> brojači cijena ovise o autoru
         */

        // a) Facet baza za AUTORE (makni samo 'autor')
        $facetDataAuthors = $request_data;
        unset($facetDataAuthors['autor']);
        $baseAuthors = (new Product())->filter(new Request($facetDataAuthors), $this->ids);

        // b) Facet baza za CIJENE (makni samo 'price_ranges')
        $facetDataPrices = $request_data;
        unset($facetDataPrices['price_ranges']);
        $basePrices = (new Product())->filter(new Request($facetDataPrices), $this->ids);

        // AUTORI: id-jevi i brojači iz $baseAuthors
        $authorIdsInContext = (clone $baseAuthors)
            ->whereNotNull('author_id')
            ->distinct()
            ->pluck('author_id')
            ->toArray();

        $authorCounts = (clone $baseAuthors)
            ->selectRaw('author_id, COUNT(*) as total')
            ->whereNotNull('author_id')
            ->groupBy('author_id')
            ->pluck('total', 'author_id')
            ->toArray();

        $authors = Author::query()
            ->when(!empty($authorIdsInContext), fn($q) => $q->whereIn('id', $authorIdsInContext))
            ->when($this->authorSearch, fn($q) => $q->where('title', 'like', '%'.$this->authorSearch.'%'))
            ->orderBy('title')
            ->get(['id','title','slug']);

        // CIJENE: agregacija iz $basePrices
        // Zamijeni 'price' ako ti je kolona drukčijeg imena (npr. sale_price, price_eur...)
        $priceAgg = (clone $basePrices)->selectRaw("
            SUM(CASE WHEN price >= 0  AND price < 10  THEN 1 ELSE 0 END) AS c_0_10,
            SUM(CASE WHEN price >= 10 AND price < 20  THEN 1 ELSE 0 END) AS c_10_20,
            SUM(CASE WHEN price >= 20 AND price < 30  THEN 1 ELSE 0 END) AS c_20_30,
            SUM(CASE WHEN price >= 30 AND price < 40  THEN 1 ELSE 0 END) AS c_30_40,
            SUM(CASE WHEN price >= 40 AND price < 50  THEN 1 ELSE 0 END) AS c_40_50,
            SUM(CASE WHEN price >= 50 AND price < 100 THEN 1 ELSE 0 END) AS c_50_100,
            SUM(CASE WHEN price >= 100                    THEN 1 ELSE 0 END) AS c_100_plus
        ")->first();

        $priceCounts = [
            '0-10'   => (int) ($priceAgg->c_0_10 ?? 0),
            '10-20'  => (int) ($priceAgg->c_10_20 ?? 0),
            '20-30'  => (int) ($priceAgg->c_20_30 ?? 0),
            '30-40'  => (int) ($priceAgg->c_30_40 ?? 0),
            '40-50'  => (int) ($priceAgg->c_40_50 ?? 0),
            '50-100' => (int) ($priceAgg->c_50_100 ?? 0),
            '100+'   => (int) ($priceAgg->c_100_plus ?? 0),
        ];

        return view('livewire.front.product-category-list', [
            'products'     => $products,
            'authors'      => $authors,
            'authorCounts' => $authorCounts, // zavisno o cijeni
            'priceCounts'  => $priceCounts,  // zavisno o autoru
        ]);
    }

    public function paginationView()
    {
        return 'vendor.pagination.bootstrap-livewire';
    }
}
