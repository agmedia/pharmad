<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Orders\OrderProduct;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\ProductOption;
use Darryldecode\Cart\ItemCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductHelper
{

    /**
     * @param Product       $product
     * @param Category|null $category
     * @param Category|null $subcategory
     *
     * @return string
     */
    public static function categoryString(Product $product, Category $category = null, Category $subcategory = null): string
    {
        $data        = static::resolveCategories($product, $category, $subcategory);
        $category    = $data['category'];
        $subcategory = $data['subcategory'];
        $catstring   = '';

        if ($category) {
            $catstring = '<span class="fs-xs ms-1"><a href="' . route('catalog.route', ['group' => Str::slug($category->group), 'cat' => $category->slug]) . '">' . $category->title . '</a> ';
        }

        if ($subcategory) {
            $substring = '</span><span class="fs-xs ms-1"><a href="' . route('catalog.route',
                    ['group' => Str::slug($category->group), 'cat' => $category->slug, 'subcat' => $subcategory->slug]) . '">' . $subcategory->title . '</a></span>';

            return $catstring . $substring;
        }

        return $catstring;
    }


    /**
     * @param Product       $product
     * @param Category|null $category
     * @param Category|null $subcategory
     *
     * @return string
     */
    public static function url(Product $product, Category $category = null, Category $subcategory = null): string
    {
        $data        = static::resolveCategories($product, $category, $subcategory);
        $category    = $data['category'];
        $subcategory = $data['subcategory'];

        if ($subcategory) {
            return Str::slug($category->group) . '/' . $category->slug . '/' . $subcategory->slug . '/' . $product->slug;
        }

        if ($category) {
            return Str::slug($category->group) . '/' . $category->slug . '/' . $product->slug;
        }

        return '/';
    }


    /**
     * @param Product       $product
     * @param Category|null $category
     * @param Category|null $subcategory
     *
     * @return array
     */
    public static function resolveCategories(Product $product, Category $category = null, Category $subcategory = null): array
    {
        if ( ! $category) {
            $category = $product->category();
        }

        if ( ! $subcategory) {
            $psub = $product->subcategory();

            if ($psub && $category && $category->subcategories()->count() > 0) {
                foreach ($category->subcategories()->get() as $sub) {
                    if ($sub->id == $psub->id) {
                        $subcategory = $psub;
                    }
                }
            }
        }

        return [
            'category'    => $category,
            'subcategory' => $subcategory
        ];
    }


    /**
     * @param Builder $query
     * @param array   $request
     *
     * @return Builder
     */
    public static function queryCategories(Builder $query, array $request): Builder
    {
        $query->whereHas('categories', function ($query) use ($request) {
            if ($request['group'] && ! $request['cat'] && ! $request['subcat']) {
                $query->where('group', $request['group']);
            }

            if ($request['cat'] && ! $request['subcat']) {
                $query->where('category_id', $request['cat']);
            }

            if ($request['subcat']) {
                $query->where('category_id', $request['subcat']);
            }
        });

        return $query;
    }


    /**
     * @param string $path
     *
     * @return string
     */
    public static function getCleanImageTitle(string $path): string
    {
        $from   = strrpos($path, '/') + 1;
        $length = strrpos($path, '-') - $from;

        return substr($path, $from, $length);
    }


    /**
     * @param string $path
     *
     * @return string
     */
    public static function getFullImageTitle(string $path): string
    {
        $from   = strrpos($path, '/') + 1;
        $length = strrpos($path, '.') - $from;

        return substr($path, $from, $length);
    }


    /**
     * @param string $title
     *
     * @return string
     */
    public static function setFullImageTitle(string $title): string
    {
        return $title . '-' . Str::random(4);
    }


    /**
     * @param int|string $order_id
     *
     * @return bool
     */
    public static function makeAvailable($order_id): bool
    {
        $ops = OrderProduct::query()->where('order_id', $order_id)->get();

        foreach ($ops as $op) {
            Product::query()->where('id', $op->product_id)->increment('quantity', $op->quantity);
            Product::query()->where('id', $op->product_id)->update(['status' => 1]);
        }

        return true;
    }


    /**
     * @param ProductOption $option
     *
     * @return string
     */
    public static function getColorOptionStyle(ProductOption $option, bool $parent = false): string
    {
        if ($parent) {
            if ($option->top->value_opt) {
                return 'background: linear-gradient(45deg, ' . $option->top->value . ' 50%, ' . $option->top->value_opt . ' 50%);';

            } else {
                return 'background-color:' . $option->top->value;
            }
        }

        if ($option->title->value_opt) {
            return 'background: linear-gradient(45deg, ' . $option->title->value . ' 50%, ' . $option->title->value_opt . ' 50%);';

        } else {
            return 'background-color:' . $option->title->value;
        }

        return '';
    }


    /**
     * @param array $item
     *
     * @return array
     */
    public static function hasOptionFromCartItem(array|ItemCollection $item): array
    {
        //  Log::info('public static function hasOptionFromCartItem(array $item)');
        // Log::info('1');
        //  Log::info($item);

        //return [];

        if (isset($item['attributes']['options']) && ! empty($item['attributes']['options'])) {
            // Log::info('2');
            $option = collect($item['attributes']['options'])->first();

            //  Log::info($option);
            $product_option = ProductOption::query()->find($option['id']);

            if ($product_option) {
                //Log::info('3');
                // Log::info('public static function hasOptionFromCartItem(array $item): array ::::::: ');
                $option['option_id'] = $product_option->option_id;
                $option['parent_id'] = $product_option->parent_id;
            }

            return $option;
        }

        //  Log::info('4');

        return [];
    }


    /**
     * @param string $sku
     *
     * @return bool
     */
    public static function isDuplicateOptionSku(string $sku, $option_id): bool
    {
        $exist = ProductOption::query()->where('sku', $sku)->first();

        //dd($exist, $option_id);

        if ($exist && $exist->option_id != $option_id) {
            return true;
        }

        return false;
    }
}
