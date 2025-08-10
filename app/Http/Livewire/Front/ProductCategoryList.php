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

    public function idChanged($data)
    {
        $this->start = $data['start'] ?? $this->start;
        $this->end   = $data['end']   ?? $this->end;
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

        // 3) Sastavi $request_data za GLAVNI upit (s odabranim autorima)
        $request_data = [];
        if ($this->group)   $request_data['group'] = $this->group;
        if ($this->cat)     $request_data['cat'] = $this->cat;
        if ($this->subcat)  $request_data['subcat'] = $this->subcat;
        if (!empty($authorIdsFilter)) $request_data['autor'] = $authorIdsFilter; // array ID-eva
        if (!empty($this->publishers)) $request_data['nakladnik'] = $this->publishers;
        if ($this->start && strlen($this->start) == 4) $request_data['start'] = $this->start;
        if ($this->end   && strlen($this->end) == 4)   $request_data['end']   = $this->end;
        if ($this->sort) $request_data['sort'] = $this->sort;

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

        // 6) FACETS: bazni upit bez autora -> iz njega izvučemo relevantne autore
        $facetData = $request_data;
        unset($facetData['autor']); // makni autore iz facets-a
        $facetReq = new Request($facetData);

        $base = (new Product())->filter($facetReq, $this->ids);

        // a) ID-evi autora prisutni u trenutnom setu
        $authorIdsInContext = (clone $base)
            ->whereNotNull('author_id')
            ->distinct()
            ->pluck('author_id')
            ->toArray();

        // b) (Opcionalno) brojači po autoru
        $authorCounts = (clone $base)
            ->selectRaw('author_id, COUNT(*) as total')
            ->whereNotNull('author_id')
            ->groupBy('author_id')
            ->pluck('total', 'author_id')
            ->toArray();

        // c) Sidebar lista autora: samo oni relevantni + pretraga
        $authors = Author::query()
            ->when(!empty($authorIdsInContext), fn($q) => $q->whereIn('id', $authorIdsInContext))
            ->when($this->authorSearch, fn($q) => $q->where('title', 'like', '%'.$this->authorSearch.'%'))
            ->orderBy('title')
            ->get(['id','title','slug']);

        return view('livewire.front.product-category-list', [
            'products'     => $products,
            'authors'      => $authors,
            'authorCounts' => $authorCounts, // po želji koristi u viewu
        ]);
    }


    public function paginationView()
    {
        return 'vendor.pagination.bootstrap-livewire';
    }
}
