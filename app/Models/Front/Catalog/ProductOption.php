<?php

namespace App\Models\Front\Catalog;

use App\Helpers\OptionHelper;
use App\Models\Front\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Attributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProductOption extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_option';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var string[]
     */
    protected $appends = ['title'];

    /**
     * @var Model
     */
    protected $resource;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function option()
    {
        return $this->hasOne(Options::class, 'id', 'option_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function top()
    {
        return $this->hasOne(Options::class, 'id', 'parent_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }


    /**
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->option()->first() ?: '';
    }



    public static function getList()
    {
        $response = [];
        $values = Options::query()->get();

        foreach ($values as $value) {
            $response[$value->group]['group'] = $value->ime_grupe;
            $response[$value->group]['items'][] = [
                'id' => $value->id,
                'title' => $value->title,
                'sort_order' => $value->sort_order,
                'style'      => OptionHelper::getStyle($value),
            ];
        }

        return $response;
    }


    public static function getFromCartData(array $data)
    {
        $product_option = ProductOption::query()->where(function ($query) use ($data) {
            $query->where('product_id', $data['id'])->orWhere('id', $data['id']);
        })->where('option_id', $data['option_id']);

        if (isset($data['parent_id']) && $data['parent_id']) {
            $product_option->where('parent_id', $data['parent_id']);
        } else {
            $product_option->where('parent_id', 0);
        }

        return $product_option->first();
    }


    /**
     * @param float|int $price
     *
     * @return string
     */
    public static function hasPriceAddition(float|int $price): string
    {
        $price_addition = '';

        if ($price > 0) {
            $price_addition = ' +' . $price;
        }

        return $price_addition;
    }

}
