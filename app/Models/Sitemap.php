<?php

namespace App\Models;

use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Brand;
use App\Models\Front\Recepti;
use App\Models\Front\Blog;
use App\Models\Front\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class Sitemap
 * @package App\Models
 */
class Sitemap
{

    /**
     * @var string|null
     */
    private $sitemap;

    /**
     * @var array
     */
    private $response = [];


    /**
     * Sitemap constructor.
     *
     * @param string|array|null $sitemap
     */
    public function __construct(string|array $sitemap = null)
    {
        if (is_array($sitemap)) {
            $this->sitemap = $sitemap;

            return $this->getIndexLastMods();
        }

        $this->sitemap = $this->setSitemap($sitemap);
    }


    /**
     * @return string|null
     */
    public function getSitemap()
    {
        return $this->sitemap;
    }


    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }


    /**
     * @param string $sitemap
     *
     * @return array
     */
    private function setSitemap(string $sitemap)
    {
        if ( ! $sitemap) {
            return $sitemap;
        }

        if ($sitemap == 'pages' || $sitemap == 'pages.xml') {
            return $this->getPages();
        }

        if ($sitemap == 'categories' || $sitemap == 'categories.xml') {
            return $this->getCategories();
        }

        if ($sitemap == 'products' || $sitemap == 'products.xml') {
            return $this->getProducts();
        }

        if ($sitemap == 'brands' || $sitemap == 'brands.xml') {
            return $this->getBrands();
        }

        if ($sitemap == 'images' || $sitemap == 'img') {
            return $this->getImages();
        }
    }


    /**
     * @return void
     */
    private function getIndexLastMods(): void
    {
        foreach ($this->sitemap as $group) {
            if ($group == 'pages') {
                $last = Page::query()->where('group', 'page')->where('status', '=', 1)->orderBy('updated_at', 'desc')->pluck('updated_at')->first();
            }
            if ($group == 'categories') {
                $last = Category::query()->where('status', '=', 1)->orderBy('updated_at', 'desc')->pluck('updated_at')->first();
            }
            if ($group == 'products') {
                $last = Product::query()->where('status', '=', 1)->orderBy('updated_at', 'desc')->pluck('updated_at')->first();
            }
            if ($group == 'brands') {
                $last = Brand::query()->where('status', '=', 1)->orderBy('updated_at', 'desc')->pluck('updated_at')->first();
            }

            $mod = Carbon::parse($last)->tz('UTC')->toAtomString();

            $this->response[] = [
                'url' => route('sitemap', ['sitemap' => $group]),
                'lastmod' => $mod
            ];
        }
    }


    /**
     * @return array
     */
    private function getImages(): array
    {
        $products = Product::query()->active()->hasStock()->with('images');

        foreach ($products->get() as $product) {
            $this->response[$product->id] = [
                'loc' => url($product->translation->url)
            ];

            $this->response[$product->id]['images'][] = [
                'loc' => $product->image
            ];

            foreach ($product->images as $image) {
                $this->response[$product->id]['images'][] = [
                    'loc' => config('settings.images_domain') . $image->image
                ];
            }
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getPages()
    {
        $pages = Page::query()->where('group', 'page')->where('status', '=', 1)->get();
        $blogs = Blog::query()->where('status', '=', 1)->get();

        $recepti = Recepti::query()->where('status', '=', 1)->get();

        $this->response[] = [
            'url' => route('index'),
            'lastmod' => Carbon::now()->startOfMonth()->tz('UTC')->toAtomString()
        ];

        $this->response[] = [
            'url' => route('kontakt'),
            'lastmod' => Carbon::now()->startOfYear()->tz('UTC')->toAtomString()
        ];

        /*   $this->response[] = [
               'url' => route('faq'),
               'lastmod' => Carbon::now()->startOfYear()->tz('UTC')->toAtomString()
           ];*/

        foreach ($pages as $page) {
            if ($page->translation->slug == 'homepage') {
                $this->response[] = [
                    'url' => route('catalog.route.page', ['page' => $page->translation->slug]),
                    'lastmod' => $page->updated_at->tz('UTC')->toAtomString()
                ];
            }
        }

        foreach ($blogs as $blog) {
            $this->response[] = [
                'url' => route('catalog.route.blog', ['cat' => $blog]),
                'lastmod' => $blog->updated_at->tz('UTC')->toAtomString()
            ];
        }

        foreach ($recepti as $recept) {
            $this->response[] = [
                'url' => route('catalog.route.recepti', ['cat' => $recept]),
                'lastmod' => $recept->updated_at->tz('UTC')->toAtomString()
            ];
        }




        return $this->response;
    }


    /**
     * @return array
     */
    private function getCategories()
    {
        $categories = Category::query()->active()->topList()->with('subcategories')->get();

        foreach ($categories as $category) {
            $this->response[] = [
                'url' => route('catalog.route', ['group' => Str::slug($category->group), 'cat' => $category->slug]),
                'lastmod' => $category->updated_at->tz('UTC')->toAtomString()
            ];

            foreach ($category->subcategories()->get() as $subcategory) {
                $this->response[] = [
                    'url' => route('catalog.route', ['group' => Str::slug($category->group), 'cat' => $category->slug, 'subcat' => $subcategory->slug]),
                    'lastmod' => $subcategory->updated_at->tz('UTC')->toAtomString()
                ];
            }
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getProducts()
    {
        $products = Product::query()->active()->hasStock()->get();

        foreach ($products as $product) {
            $url = url($product->translation->url);

            if (Str::contains($url, '/hr/')) {
                $url = str_replace('/hr/', '/', $url);
            }

            $this->response[] = [
                'url' => $url,
                'lastmod' => $product->updated_at->tz('UTC')->toAtomString()
            ];
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getBrands()
    {
        $brands = Brand::query()->active()->get();

        $this->response[] = [
            'url' => route('catalog.route.brand'),
            'lastmod' => Carbon::now()->startOfMonth()->tz('UTC')->toAtomString()
        ];

        foreach ($brands as $brand) {

            $url = url($brand->translation->url);

            if (Str::contains($url, '/hr/')) {
                $url = str_replace('/hr/', '/', $url);
            }


            $this->response[] = [
                'url' => $url,
                'lastmod' => $brand->updated_at->tz('UTC')->toAtomString()
            ];

            /*$cats = Category::query()->topList()->whereHas('products', function ($query) use ($author) {
                $query->where('author_id', $author->id);
            })->with('subcategories')->get();

            if ($cats) {
                foreach ($cats as $category) {
                    $this->response[] = [
                        'url' => route('catalog.route.author', ['author' => $author->slug, 'cat' => $category->slug]),
                        'lastmod' => $author->updated_at->tz('UTC')->toAtomString()
                    ];

                    foreach ($category->subcategories()->get() as $subcategory) {
                        $this->response[] = [
                            'url' => route('catalog.route.author', ['author' => $author->slug, 'cat' => $category->slug, 'subcat' => $subcategory->slug]),
                            'lastmod' => $author->updated_at->tz('UTC')->toAtomString()
                        ];
                    }
                }
            }*/
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getRecepti()
    {
        $recepti = Recepti::query()->active()->get();

        $this->response[] = [
            'url' => route('catalog.route.recepti'),
            'lastmod' => Carbon::now()->startOfMonth()->tz('UTC')->toAtomString()
        ];

        foreach ($recepti as $recept) {
            $this->response[] = [
                'url' => url($recept->translation->url),
                'lastmod' => $recept->updated_at->tz('UTC')->toAtomString()
            ];
        }

        return $this->response;
    }
}
