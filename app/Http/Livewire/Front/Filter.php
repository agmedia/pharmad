<?php

namespace App\Http\Livewire\Front;

use App\Helpers\FilterHelper;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Filter extends Component
{
    public $categories = [];

    public $ids = [];

    public $group = '';

    public $cat;

    public $subcat;

    public $brand;


    public function mount()
    {
        $this->categories = $this->getCategories();

        Log::debug($this->categories);
    }

    public function render()
    {
        return view('livewire.front.filter');
    }


    private function getCategories()
    {
        return FilterHelper::getCategories($this->group);
    }
}
