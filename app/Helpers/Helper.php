<?php


namespace App\Helpers;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Marketing\Action;
use App\Models\Back\Settings\Settings;
use App\Models\Back\Widget\WidgetGroup;
use App\Models\Front\Blog;
use App\Models\Front\Loyalty;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;

use Darryldecode\Cart\CartCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\False_;

class Helper
{

    /**
     * @param float $price
     * @param int   $discount
     *
     * @return float|int
     */
    public static function calculateDiscountPrice(float $price, int $discount, string $type)
    {
        if ($type == 'F') {
            return $price - $discount;
        }

        return $price - ($price * ($discount / 100));
    }


    /**
     * @param $list_price
     * @param $seling_price
     *
     * @return float|int
     */
    public static function calculateDiscount($list_price, $seling_price, string $type = 'P')
    {
        $list_price   = self::normalizePriceValue($list_price);
        $seling_price = self::normalizePriceValue($seling_price);

        if ($type == 'F') {
            return $list_price - $seling_price;
        }

        if ($list_price <= 0) {
            return 0;
        }

        return (($list_price - $seling_price) / $list_price) * 100;
    }


    private static function normalizePriceValue($price): float
    {
        if (is_int($price) || is_float($price)) {
            return (float) $price;
        }

        if (!is_string($price)) {
            return (float) $price;
        }

        $price = trim($price);

        if ($price === '') {
            return 0.0;
        }

        $price = preg_replace('/[^\d,.\-]/', '', $price);

        if ($price === '' || $price === null) {
            return 0.0;
        }

        if (is_numeric($price)) {
            return (float) $price;
        }

        $commaPos = strrpos($price, ',');
        $dotPos   = strrpos($price, '.');

        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $price = str_replace('.', '', $price);
                $price = str_replace(',', '.', $price);
            } else {
                $price = str_replace(',', '', $price);
            }
        } elseif ($commaPos !== false) {
            $price = str_replace(',', '.', $price);
        }

        return (float) $price;
    }


    /**
     * @return string[]
     */
    public static function abc()
    {
        return ['A', 'B', 'C', 'Ć', 'Č', 'D', 'Đ', 'Dž', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'Lj', 'M', 'N', 'Nj', 'O', 'P', 'R', 'S', 'Š', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ž'];
    }


    /**
     * @param string $target
     * @param bool   $builder
     *
     * @return array|false|Collection
     */
    public static function search(string $target = '', bool $builder = false, bool $api = false)
    {
        if ($target != '') {
            $response = collect();

            $products = Product::active()->where('name', 'like', '%' . $target . '%')
                ->orWhere('meta_description', 'like', '%' . $target . '%')
                ->orWhere('description', 'like', '%' . $target . '%')
                ->orWhere('sku', 'like', '%' . $target . '%')
                ->pluck('id');

            if ( ! $products->count()) {
                $products = collect();
            }

            $preg = explode(' ', $target, 3);

            if (isset ($preg[1]) && in_array($preg[1], $preg) && ! isset($preg[2])) {
                $authors = Author::active()->where('title', 'like', '%' . $preg[0] . '%' . $preg[1] . '%')
                                 ->orWhere('title', 'like', '%' . $preg[1] . '% ' . $preg[0] . '%')
                                 ->with('products')->get();

            } elseif (isset ($preg[2]) && in_array($preg[2], $preg)) {
                $authors = Author::active()->where('title', 'like', $preg[0] . '%' . $preg[1] . '%' . $preg[2] . '%')
                                 ->orWhere('title', 'like', $preg[2] . '%' . $preg[1] . '% ' . $preg[0] . '%')
                                 ->orWhere('title', 'like', $preg[0] . '%' . $preg[2] . '% ' . $preg[1] . '%')
                                 ->orWhere('title', 'like', $preg[1] . '%' . $preg[0] . '% ' . $preg[2] . '%')
                                 ->orWhere('title', 'like', $preg[1] . '%' . $preg[2] . '% ' . $preg[0] . '%')
                                 ->with('products')->get();

            } else {
                $authors = Author::active()->where('title', 'like', '%' . $preg[0] . '%')
                                 ->with('products')->get();
            }

            foreach ($authors as $author) {
                $products = $products->merge($author->products->pluck('id'));
            }

            if ($api) {
                $products = $products->take(5);
            }

            $response->put('products', $products->unique()->flatten());

            if ($builder) {
                return $response;
            }

            return $response['products']->toJson();
        }

        return false;
    }


    /**
     * @param Builder $query
     * @param string  $search
     *
     * @return Builder
     */
    public static function searchByTitle(Builder $query, string $search): Builder
    {
        $preg = explode(' ', $search, 3);

        if (isset ($preg[1]) && in_array($preg[1], $preg) && ! isset($preg[2])) {
            $query->where('title', 'like', '%' . $preg[0] . '%' . $preg[1] . '%')
                  ->orWhere('title', 'like', '%' . $preg[1] . '% ' . $preg[0] . '%');

        } elseif (isset ($preg[2]) && in_array($preg[2], $preg)) {
            $query->where('title', 'like', $preg[0] . '%' . $preg[1] . '%' . $preg[2] . '%')
                  ->orWhere('title', 'like', $preg[2] . '%' . $preg[1] . '% ' . $preg[0] . '%')
                  ->orWhere('title', 'like', $preg[0] . '%' . $preg[2] . '% ' . $preg[1] . '%')
                  ->orWhere('title', 'like', $preg[1] . '%' . $preg[0] . '% ' . $preg[2] . '%')
                  ->orWhere('title', 'like', $preg[1] . '%' . $preg[2] . '% ' . $preg[0] . '%');

        } else {
            $query->where('title', 'like', '%' . $preg[0] . '%');
        }

        return $query;
    }


    /**
     * @param $cat
     * @param $subcat
     *
     * @return mixed
     */
    public static function getRelated($cat = null, $subcat = null)
    {
        $related = [];

        if ($subcat) {
            $related = $subcat->products()->inRandomOrder()->take(10)->get();

        } else {
            if ($cat) {
                $related = $cat->products()->inRandomOrder()->take(10)->get();
            }
        }

        if ($related->count() < 9) {
            $related->merge(Product::query()->inRandomOrder()->take(10 - $related->count())->get());
        }

        return $related;
    }


    /**
     * @param string $description
     *
     * @return false|string
     */
    public static function setDescription(string $description)
    {
        if ($description == '') {
            return '';
        }

        $ids = Cache::remember('wg_ids', config('cache.life'), function () use ($description) {
            $iterator = substr_count($description, '++');
            $offset   = 0;
            $ids      = [];

            for ($i = 0; $i < $iterator / 2; $i++) {
                $from  = strpos($description, '++', $offset) + 2;
                $to    = strpos($description, '++', $from + 2);
                $ids[] = substr($description, $from, $to - $from);

                $offset = $to + 2;
            }

            return $ids;
        });

        $wgs = Cache::remember('wgs', config('cache.life'), function () use ($ids) {
            return WidgetGroup::whereIn('id', $ids)->orWhereIn('slug', $ids)->where('status', 1)->with('widgets')->get();
        });

        foreach ($ids as $id) {
            $description = Cache::remember('wg.' . $id, config('cache.life'), function () use ($wgs, $description, $id) {
                return static::resolveDescription($wgs, $description, $id);
            });
        }

        return $description;
    }


    /**
     * @param Collection $wgs
     * @param string     $description
     * @param string     $id
     *
     * @return string
     */
    private static function resolveDescription(Collection $wgs, string $description, string $id): string
    {
        $wg = $wgs->where('id', $id)->first();

        if ( ! $wg) {
            $wg = $wgs->where('slug', $id)->first();

            if ( ! $wg) {
                return str_replace(
                    '++' . $id . '++',
                    view('front.layouts.widget.widget_empty', ['data' => 'Widget not found.']),
                    $description
                );
            }
        }

        $widgets = [];

        if ($wg->template == 'product_carousel' || $wg->template == 'page_carousel') {
            $widget = $wg->widgets()->first();
            $data   = unserialize($widget->data);

            if (static::isDescriptionTarget($data, 'product')) {
                $items     = static::products($data)->get();
                $tablename = 'product';
            }

            if (static::isDescriptionTarget($data, 'blog')) {
                $items     = static::blogs($data)->get();
                $tablename = 'blog';


            }

            if (static::isDescriptionTarget($data, 'category')) {
                $items     = static::category($data)->get();


                $tablename = 'category';
            }

            if (static::isDescriptionTarget($data, 'product_category')) {
                $items     = static::product_category($data)->get();


                $tablename = 'product_category';
            }


            if (static::isDescriptionTarget($data, 'author')) {
                $items     = static::author($data)->get();
                $tablename = 'author';
            }

            if (static::isDescriptionTarget($data, 'reviews')) {
                $items     = static::dummyReviews();
                $tablename = 'reviews';
            }

            $widgets = [
                'title'      => $widget->title,
                'subtitle'   => $widget->subtitle,
                'url'        => $widget->url,
                'tablename'  => $tablename,
                'css'        => $data['css'],
                'container'  => (isset($data['container']) && $data['container'] == 'on') ? 1 : null,
                'background' => (isset($data['background']) && $data['background'] == 'on') ? 1 : null,
                'items'      => $items
            ];

        } else {
            foreach ($wg->widgets()->orderBy('sort_order')->get() as $widget) {
                $data = unserialize($widget->data);



                $widgets[] = [
                    'title'    => $widget->title,
                    'subtitle' => $widget->subtitle,
                    'color'    => $widget->badge,
                    'url'      => $widget->url,
                    'image'    => $widget->thumb,
                    'width'    => $widget->width,
                    'right'    => (isset($data['right']) && $data['right'] == 'on') ? 1 : null,
                ];
            }
        }



        return str_replace(
            '++' . $id . '++',
            view('front.layouts.widget.widget_' . $wg->template, ['data' => $widgets]),
            $description
        );
    }


    /**
     * @param array  $data
     * @param string $target
     *
     * @return bool
     */
    public static function isDescriptionTarget(array $data, string $target): bool
    {
        if (isset($data['target']) && $data['target'] == $target) {
            return true;
        }
        if (isset($data['group']) && $data['group'] == $target) {
            return true;
        }

        return false;
    }


    /**
     * @param string $text
     *
     * @return string
     */
    public static function resolveFirstLetter(string $text): string
    {
        $letter = substr($text, 0, 1);

        if (in_array(substr($text, 0, 2), ['Nj', 'Lj', 'Š', 'Č', 'Ć', 'Ž', 'Đ'])) {
            $letter = substr($text, 0, 2);
        }

        if (in_array(substr($text, 0, 3), ['Dž', 'Đ'])) {
            $letter = substr($text, 0, 3);
        }

        return $letter;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function products(array $data): Builder
    {
        $prods = (new Product())->newQuery();

        $prods->active()->available();

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $prods->popular();
        }

        $prods->distinct()->last();

        if (isset($data['list']) && $data['list']) {
            $prods->whereIn('id', $data['list']);
        }

        return $prods->with('author');
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function blogs(array $data): Builder
    {
        $blogs = (new Blog())->newQuery();

        $blogs->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $blogs->last();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $blogs->popular();
        }

        if (isset($data['list']) && $data['list']) {
            $blogs->whereIn('id', $data['list']);
        }

        return $blogs;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function category(array $data): Builder
    {
        $category = (new Category())->newQuery();

        $category->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $category->latest();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $category->latest();
        }

        if (isset($data['list']) && $data['list']) {
            $category->whereIn('id', $data['list']);
        }

        return $category;
    }


    private static function product_category(array $data): Builder
    {
        $product = (new Product())->newQuery();

        $product->where('status', 1);

        // Filtriraj po kategorijama
        if (!empty($data['list'])) {
            $product->whereHas('categories', function (Builder $query) use ($data) {
                $query->whereIn('categories.id', $data['list']);
            });
        }

        // Novi proizvodi
        if (!empty($data['new']) && $data['new'] === 'on') {
            $product->orderBy('created_at', 'desc');
        }

        // Popularni proizvodi – ovo pretpostavlja da postoji kolona `views` ili slično
        if (!empty($data['popular']) && $data['popular'] === 'on') {
            $product->orderBy('viewed', 'desc'); // prilagodi prema tvojoj logici popularnosti
        }
        return $product->limit(15);
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function author(array $data): Builder
    {
        $author = (new Author())->newQuery();

        $author->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $author->latest();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $author->latest();
        }

        if (isset($data['list']) && $data['list']) {
            $author->whereIn('id', $data['list']);
        }

        return $author;
    }


    /**
     * @param string $tag
     *
     * @return \Illuminate\Cache\TaggedCache|mixed|object
     */
    public static function resolveCache(string $tag): ?object
    {
        if (env('APP_ENV') == 'local') {
            return Cache::getFacadeRoot();
        }

        return Cache::tags([$tag]);
    }


    /**
     * @param string $tag
     * @param string $key
     *
     * @return object|bool|mixed|null
     */
    public static function flushCache(string $tag, string $key)
    {
        if (env('APP_ENV') == 'local') {
            return Cache::getFacadeRoot();
        }

        return Cache::tags([$tag])->forget($key);
    }


    /**
     * @param bool $slug
     *
     * @return string
     */
    public static function categoryGroupPath(bool $slug = false): string
    {
        if ($slug) {
            return Str::slug(config('settings.group_path'));
        }

        return config('settings.group_path');
    }

    /**
     * @param array  $data
     * @param string $tag
     * @param        $target
     *
     * @return string
     */
    public static function resolveSlug(array $data, string $tag = 'title', $target = null): string
    {
        $slug = null;

        if ($target) {
            $product = Product::where('id', $target)->first();

            if ($product) {
                $slug = $product->slug;
            }
        }

        $slug  = $slug ?: Str::slug($data[$tag]);
        $exist = Product::where('slug', $slug)->count();

        $cat_exist = Category::where('slug', $slug)->count();

        if (($cat_exist || $exist > 1) && $target) {
            return $slug . '-' . time();
        }

        if (($cat_exist || $exist) && ! $target) {
            return $slug . '-' . time();
        }

        return $slug;
    }


    /**
     * @param $cart
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasSpecialCartCondition($cart = null)
    {
        $condition     = false;
        $has_condition = false;

        if ($cart->getTotal() > 50) {
            $has_condition = 10;
        }
        if ($cart->getTotal() > 100) {
            $has_condition = 15;
        }
        if ($cart->getTotal() > 200) {
            $has_condition = 20;
        }

        if ($has_condition && self::isDateBetween()) {
            $value    = self::calculateDiscountPrice($cart->getTotal(), $has_condition, 'P');
            $discount = $cart->getTotal() - $value;

            $condition = new CartCondition(array(
                'name'       => config('settings.special_action.title'),
                'type'       => 'special',
                'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                'value'      => '-' . $discount,
                'attributes' => [
                    'description' => '',
                    'geo_zone'    => ''
                ]
            ));
        }

        return $condition;
    }


    /**
     * @param        $cart
     * @param string $coupon
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasCouponCartConditions($cart, string $coupon = '')
    {
        $condition = false;
        $actions   = Action::query()->where('group', 'total')->get();

        if ($actions->count()) {
            foreach ($actions as $action) {
                if ($action->isValid($coupon)) {
                    $value    = self::calculateDiscountPrice($cart->getTotal(), $action->discount, $action->type);
                    $discount = $cart->getTotal() - $value;

                    $condition = new CartCondition(array(
                        'name'       => $action->title,
                        'type'       => 'special',
                        'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                        'value'      => '-' . $discount,
                        'attributes' => $action->setConditionAttributes($coupon)
                    ));
                }
            }
        }

        return $condition;
    }

    /**
     * @param        $cart
     * @param string $coupon
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasLoyaltyCartConditions($cart, int $loyalty = 0)
    {
        $condition = false;
        $has_loyalty   = Loyalty::hasLoyalty();

        if ($has_loyalty) {
            $discount = Loyalty::calculateLoyalty($loyalty);

            if ($cart->getTotal() > $discount) {
                $condition = new CartCondition(array(
                    'name'       => 'Loyalty',
                    'type'       => 'special',
                    'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                    'value'      => '-' . $discount,
                    'attributes' => [
                        'type'        => 'loyalty',
                        'description' => 'Loyalty Program'
                    ]
                ));
            }
        }

        return $condition;
    }


    /**
     * @param $cart
     *
     * @return false|mixed
     */
    public static function isCouponUsed($cart)
    {
        $coupon = false;
        $items = $cart->getContent();

        foreach ($items as $item) {
            if ($item->getConditions()->getType() == 'coupon') {
                $coupon = $item->getConditions()->getTarget();
            }
        }

        foreach ($cart->getConditions() as $condition) {
            if (isset($condition->getAttributes()['type']) && $condition->getAttributes()['type'] == 'coupon' && floatval($condition->getValue()) < 0) {
                $coupon = $condition->getAttributes()['description'];
            }
        }



        return $coupon;
    }


    /**
     * @param $date
     *
     * @return bool
     */
    public static function isDateBetween($date = null): bool
    {
        if (config('settings.special_action.start')) {
            $now   = $date ?: Carbon::now();
            $start = Carbon::createFromFormat('d/m/Y H:i:s', config('settings.special_action.start'));
            $end   = Carbon::createFromFormat('d/m/Y H:i:s', config('settings.special_action.end'));

            if ($now->isBetween($start, $end)) {
                return true;
            }
        }

        return false;
    }

    public static function humanizeSlug(string $slug): string
    {
        $s = str_replace(['-','_'], ' ', $slug);
        $s = mb_strtolower($s, 'UTF-8');
        return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($s, 1, null, 'UTF-8');
    }


    private static function dummyReviews()
    {
        return collect([
            (object)[
                'id' => 1,
                'product_id' => 101,
                'order_id' => 1001,
                'user_id' => 501,
                'lang' => 'hr',
                'fname' => 'Ivana Čorić',
                'lname' => 'Horvat',
                'email' => 'ana.horvat@example.com',
                'avatar' => 'avatars/ana.jpg',
                'message' => 'Oduševila me brzina dostave, cijena i usluga....osim jednog naručenog proizvoda dobila sam i par testera...sve pohvale za PharmaAD Farmaciju...od sada sam vaš najvjerniji kupac ☺️',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 1,
                'status' => 1,
                'created_at' => '2025-08-01 10:15:00',
                'updated_at' => '2025-08-01 10:15:00',
            ],
            (object)[
                'id' => 2,
                'product_id' => 102,
                'order_id' => 1002,
                'user_id' => 502,
                'lang' => 'hr',
                'fname' => 'Valentina',
                'lname' => '',
                'email' => 'ivan.maric@example.com',
                'avatar' => 'avatars/ivan.png',
                'message' => 'Oduševljena sam pristupom i kvalitetom usluge. Brza dostava, sve što sam naručio bilo je adekvatno zapakirano i zaštićeno. Kupnju sam obavila jednostavno i lako. U paketu je bio i poklon koji me također iznenadio. Ispunio je moja očekivanja, kupovat ću opet kod vas. 🍀💛',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 0,
                'status' => 1,
                'created_at' => '2025-08-02 11:00:00',
                'updated_at' => '2025-08-02 11:00:00',
            ],
            (object)[
                'id' => 3,
                'product_id' => 103,
                'order_id' => 1003,
                'user_id' => 503,
                'lang' => 'hr',
                'fname' => 'Cat',
                'lname' => 'Woman',
                'email' => 'maja.kovac@example.com',
                'avatar' => 'avatars/maja.jpg',
                'message' => 'E sad, naručujem preko weba dugi niz godina i eto odlučih kupit nešto i preko PharmAD ljekarne.
Cijene povoljne, stiglo veoma brzo i najviše su me ugodno iznenadili poklončići kojih ima podosta.
I da nije, sve bi bilo ok.
Preporučam u svakom pogledu.
Pozdrav iz Splita. 🙂👍',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 1,
                'status' => 1,
                'created_at' => '2025-08-03 09:45:00',
                'updated_at' => '2025-08-03 09:45:00',
            ],
            (object)[
                'id' => 4,
                'product_id' => 104,
                'order_id' => 1004,
                'user_id' => 504,
                'lang' => 'hr',
                'fname' => 'Sanja',
                'lname' => 'Jotanovic Adanic',
                'email' => 'petar.novak@example.com',
                'avatar' => 'avatars/petar.png',
                'message' => 'Narudžba poslana ekspresno, dostava GLS, što mi je iznimno važno. Nakon što sam otvorila paket doživila šok poklonom uz kupnju.
Sve preporuke od mene 👍.',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 0,
                'status' => 1,
                'created_at' => '2025-08-04 13:20:00',
                'updated_at' => '2025-08-04 13:20:00',
            ],
            (object)[
                'id' => 5,
                'product_id' => 105,
                'order_id' => 1005,
                'user_id' => 505,
                'lang' => 'hr',
                'fname' => 'Azra',
                'lname' => 'Begulić',
                'email' => 'lucija.peric@example.com',
                'avatar' => 'avatars/lucija.jpeg',
                'message' => 'Odlična ponuda a i usluga!☺️ Jako brza dostava, i uvijek se dobije mali (ili veći 🙈) znak pažnje!',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 1,
                'status' => 1,
                'created_at' => '2025-08-05 08:10:00',
                'updated_at' => '2025-08-05 08:10:00',
            ],
        ]);
    }



}
