<?php

namespace App\Http\Livewire\Back\Catalog;

use App\Helpers\Country;
use App\Models\Back\Catalog\Attributes\Attributes;
use App\Models\Back\Catalog\Options;
use Illuminate\Support\Str;
use Livewire\Component;

class OptionsAddition extends Component
{

    public $values = [];

    public $items = [];

    public $item = [];

    public $type = 'text';

    public function mount()
    {
        if ( ! empty($this->values)) {
            $this->sortPredefinedItems();
        }

        if (request()->has('type')) {
            $this->type = request()->input('type');
        }

        //dd($this->items);
    }


    public function addItem(array $item = [])
    {
        if (empty($item)) {
            $item = $this->getEmptyItem();
        }

        array_unshift($this->items, $item);
    }


    public function deleteItem(int $key)
    {
        unset($this->items[$key]);
    }

    public function render()
    {
        return view('livewire.back.catalog.options-addition');
    }


    private function getEmptyItem(): array
    {
        return [
            'id' => 0,
            'title' => '',
            'color' => '#000000',
            'color_opt' => NULL,
            'option_sku' => NULL,
            'color_opt_show' => false,
            'sort_order' => 0
        ];
    }


    private function sortPredefinedItems()
    {
        if ($this->type == 'color' || $this->type == 'size') {
            $values = Options::query()->where('grupa', Str::slug($this->values->group))->get()->sortBy('title');

        } else {
            $values = Attributes::query()->where('grupa', Str::slug($this->values->group))->get();
        }

        foreach ($values as $value) {
            array_push($this->items, [
                'id' => $value->id,
                'title' => $value->title,
                'color' => $value->value,
                'color_opt' => $value->value_opt,
                'option_sku' => $value->option_sku,
                'color_opt_show' => $value->value_opt ?: false,
                'sort_order' => $value->sort_order
            ]);
        }
    }
}
