<?php

namespace App\Helpers;

use App\Models\Front\Blog;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Recepti;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class Metatags
{

    public static function noFollow()
    {
        return [
            'name'    => 'robots',
            'content' => 'noindex,nofollow'
        ];
    }


    /**
     * @return array
     */
    public static function indexSchema(): array
    {
        return [
            '@context'     => 'https://schema.org',
            '@type'        => 'Pharmacy',
            '@id'          => url('/') . '#organization',
            'name'         => 'Ljekarne PharmAD',
            'image'        => asset('media/img/cover-ljekarne-pharmad.jpg'),
            'logo'         => [
                '@type' => 'ImageObject',
                'url'   => asset('media/img/logo-ljekarne-pharmad.png'),
            ],
            'url'          => url('/'),
            'email'        => 'webshop@ljekarne-pharmad.hr',
            'telephone'    => '+385994891210',
            'contactPoint' => [
                [
                    '@type'             => 'ContactPoint',
                    'contactType'       => 'customer support',
                    'telephone'         => '+385994891210',
                    'email'             => 'webshop@ljekarne-pharmad.hr',
                    'areaServed'        => 'HR',
                    'availableLanguage' => 'hr',
                ]
            ],
            'sameAs'       => [
                'https://www.facebook.com/ljekarna.pharmad/',
                'https://www.instagram.com/ljekarnepharmad/'
            ]
        ];
    }


    /**
     * @param Product|null    $prod
     * @param Collection|null $reviews
     *
     * @return array
     */
    public static function productSchema(Product $prod = null, Collection $reviews = null): array
    {
        $response = [];

        if ($prod) {
            $price = ($prod->special()) ? $prod->special() : number_format($prod->price, 2, '.', '');
            $brand = optional($prod->author)->title ?: config('app.name');
            $ean   = preg_replace('/\D+/', '', (string) $prod->ean);

            $url = url($prod->url);

            if (Str::contains($url, '/hr/')) {
                $url = str_replace('/hr/', '/', $url);
            }

            $response = [
                '@context'      => 'https://schema.org/',
                '@type'         => 'Product',
                'sku'           => $prod->sku,
                'description'   => $prod->meta_description,
                'name'          => $prod->name,
                'itemCondition' => 'https://schema.org/NewCondition',
                'mpn'           => $prod->sku,
                'image'         => [
                    '@type'  => 'ImageObject',
                    'url'    => asset($prod->image),
                    'name'   => isset($prod->alt['title']) ? $prod->alt['title'] : '',
                    'width'  => 500,
                    'height' => 500,
                ],
                'brand'         => [
                    '@type' => 'Brand',
                    'name'  => $brand,
                ],
                'offers'        => [
                    '@type'           => 'Offer',
                    'priceCurrency'   => 'EUR',
                    'price'           => (string) $price,
                    'priceValidUntil' => now()->endOfYear()->format('Y-m-d'),
                    'sku'             => $prod->sku,
                    'url'             => $url,
                    'availability'    => ($prod->quantity) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
                ],
            ];

            if (strlen($ean) == 8) {
                $response['gtin8'] = $ean;
            } elseif (strlen($ean) == 12) {
                $response['gtin12'] = $ean;
            } elseif (strlen($ean) == 13) {
                $response['gtin13'] = $ean;
            } elseif (strlen($ean) == 14) {
                $response['gtin14'] = $ean;
            }

            if (isset($reviews) and $reviews->count()) {
                $response['aggregateRating'] = [
                    '@type'       => 'AggregateRating',
                    'ratingValue' => floor($reviews->avg('stars')),
                    'reviewCount' => $reviews->count(),
                ];

                $schemaReviews = [];

                foreach ($reviews as $review) {
                    $schemaReviews[] = [
                        '@type'         => 'Review',
                        'author'        => [
                            '@type' => 'author',
                            'name'  => $review->fname,
                        ],
                        'datePublished' => Carbon::make($review->created_at)->locale('hr')->format('Y-m-d'),
                        'reviewBody'    => strip_tags($review->message),
                        'name'          => $prod->name,
                        'reviewRating'  => [
                            '@type'       => 'Rating',
                            'bestRating'  => '5',
                            'ratingValue' => floor($review->stars),
                            'worstRating' => '1'
                        ]
                    ];
                }

                $response['review'] = $schemaReviews;
            }
        }

        return $response;
    }


    /**
     * @param Blog $blog
     *
     * @return array
     */
    public static function blogSchema(Blog $blog): array
    {
        $url = LaravelLocalization::getLocalizedUrl(current_locale(), route('catalog.route.blog', ['cat' => $blog->slug]));

        $response = [
            '@context'         => 'https://schema.org/',
            '@type'            => 'BlogPosting',
            '@id'              => $url . '/#richSnippet',
            'headline'         => $blog->title,
            'description'      => strip_tags($blog->description),
            'keywords'         => $blog->translation->keywords,
            'image'            => $blog->image,
            'datePublished'    => Carbon::make($blog->created_at)->format('Y-m-d'),
            'dateModified'     => Carbon::make($blog->updated_at)->format('Y-m-d'),
            'inLanguage'       => current_locale(),
            'author'           => [
                '@type' => 'Organization',
                'name'  => config('app.name'),
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => config('app.name'),
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => ''
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'ImageObject',
                '@id'   => $url
            ]
        ];

        return $response;
    }


    /**
     * @param Recepti $recepti
     *
     * @return array
     */
    public static function recipeSchema(Recepti $recepti): array
    {
        $url = LaravelLocalization::getLocalizedUrl(current_locale(), route('catalog.route.recepti', ['cat' => $recepti->slug]));

        $response = [
            '@context'       => 'https://schema.org/',
            '@type'          => 'Recipe',
            '@id'            => $url . '/#schema',
            'name'           => $recepti->title,
            'image'          => $recepti->image,
            'description'    => strip_tags($recepti->description),
            'recipeCategory' => $recepti->category() ? $recepti->category()->title : '',
            'keywords'       => $recepti->translation->keywords,
            'datePublished'  => Carbon::make($recepti->created_at)->format('Y-m-d'),
            'inLanguage'     => current_locale(),
            'author'         => [
                '@type' => 'Organization',
                'name'  => config('app.name'),
            ],
            'publisher'      => [
                '@type' => 'Organization',
                'name'  => config('app.name'),
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => ''
                ]
            ]
        ];

        return $response;
    }


    /**
     * @param string      $uri
     * @param string|null $search_query
     *
     * @return array
     */
    public static function searchSchema(string $uri, string $search_query = null): array
    {
        if ($search_query) {
            $uri_check = substr(str_replace($search_query, '', $uri), 1);
            $target    = config('app.url') . $uri_check . '{' . $search_query . '}';

            return [
                '@context'        => 'https://schema.org/',
                '@type'           => 'WebSite',
                'url'             => config('app.url'),
                'potentialAction' => [
                    '@type'  => 'SearchAction',
                    'target' => $target,
                    'query'  => 'required',
                ],
            ];
        }

        return [];
    }


    /**
     * @return array
     */
    public static function homepageSearchActionShema(): array
    {
        return [
            '@context'        => 'https://schema.org/',
            '@type'           => 'WebSite',
            '@id'             => url('/') . '#webSite',
            'url'             => url('/'),
            'name'            => config('app.name'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => url('/pretrazi') . '?pojam={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
