<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Orders\OrderProduct;
use App\Models\Front\Catalog\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FilterHelper
{

    public static function getCategories(string $group = '')
    {
        if ($group) {
            //$response = Helper::resolveCache('categories')->remember($group, config('cache.life'), function () use ($group) {
                $response = Category::active()->topList($group)->sortByName()->with('subcategories')->withCount([
                    'products',
                    'products as products_count' => function (Builder $query) {
                        return $query->where('status', 1)
                                     ->where('quantity', '>', 0)
                                     ->where('price', '>', 0);
                    }])->get()->toArray();

                return self::resolveCategoryArray($response, 'categories');
            //});

        } else {
            //$response = Helper::resolveCache('categories')->remember($group, config('cache.life'), function () {
            $groups = Category::query()->pluck('group');

            foreach ($groups as $group) {
                if ( ! empty($group)) {
                    $name = str_replace('-', ' ', $group);
                    $name = Str::headline($name);

                    $response = Category::active()->topList($group)->sortByName()->with('subcategories')->withCount([
                        'products',
                        'products as products_count' => function (Builder $query) {
                            return $query->where('status', 1)
                                         ->where('quantity', '>', 0)
                                         ->where('price', '>', 0);
                        }])->get()->toArray();

                    $sorted[$name] = self::resolveCategoryArray($response, 'categories');
                }
            }

            return $sorted;

            //});
        }

        return $response;
    }


    /**
     * @param             $categories
     * @param string      $type
     * @param null        $target
     * @param string|null $parent_slug
     *
     * @return array
     */
    private static function resolveCategoryArray($categories, string $type, $target = null, string $parent_slug = null): array
    {
        $response = [];

        foreach ($categories as $category) {
            $url = self::resolveCategoryUrl($category, $type, $target, $parent_slug);
            $subs = null;

            if (isset($category['subcategories']) && ! empty($category['subcategories'])) {
                foreach ($category['subcategories'] as $subcategory) {
                    $sub_url = self::resolveCategoryUrl($subcategory, $type, $target, $category['slug']);

                    $subs[] = [
                        'id' => $subcategory['id'],
                        'title' => $subcategory['title'],
                        'count' => 0,//Category::find($subcategory['id'])->products()->count(),
                        'url' => $sub_url
                    ];
                }
            }

            $response[] = [
                'id' => $category['id'],
                'title' => $category['title'],
                'count' => $category['products_count'],
                'url' => $url,
                'subs' => $subs
            ];


        }

        return $response;
    }


    /**
     * @param             $category
     * @param string      $type
     * @param             $target
     * @param string|null $parent_slug
     *
     * @return string
     */
    private static function resolveCategoryUrl($category, string $type, $target, string $parent_slug = null): string
    {
        if ($type == 'author') {
            return route('catalog.route.author', [
                'author' => $target,
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);

        } elseif ($type == 'publisher') {
            return route('catalog.route.publisher', [
                'publisher' => $target,
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);

        } else {
            return route('catalog.route', [
                'group' => Str::slug($category['group']),
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);
        }
    }
}
