<?php

namespace Tests\Unit;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Marketing\Action;
use App\Services\PublicProductPrice;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PublicProductPriceTest extends TestCase
{
    public function test_it_uses_current_public_special_price(): void
    {
        $product = (new Product())->forceFill(['price' => 20, 'special' => 15, 'special_from' => '2026-09-01', 'special_to' => '2026-10-31']);
        $product->setRelation('priceListAction', (new Action())->forceFill(['title' => 'Jesenska akcija', 'coupon' => null, 'status' => 1]));

        $price = (new PublicProductPrice())->resolve($product, Carbon::parse('2026-10-01 12:00:00'));

        $this->assertSame(15.0, $price['current']);
        $this->assertTrue($price['is_special']);
        $this->assertSame('Jesenska akcija', $price['special_name']);
    }

    public function test_coupon_only_and_expired_specials_are_not_public_prices(): void
    {
        $product = (new Product())->forceFill(['price' => 20, 'special' => 15, 'special_from' => '2026-09-01', 'special_to' => '2026-10-31']);
        $product->setRelation('priceListAction', (new Action())->forceFill(['title' => 'Kupon', 'coupon' => 'TAJNO', 'status' => 1]));
        $service = new PublicProductPrice();

        $this->assertSame(20.0, $service->resolve($product, Carbon::parse('2026-10-01'))['current']);
        $product->setRelation('priceListAction', null);
        $this->assertSame(20.0, $service->resolve($product, Carbon::parse('2026-11-01'))['current']);
    }

    public function test_disabled_action_is_not_exported_as_a_public_special(): void
    {
        $product = (new Product())->forceFill(['price' => 20, 'special' => 15]);
        $product->setRelation('priceListAction', (new Action())->forceFill(['title' => 'Isključena akcija', 'coupon' => null, 'status' => 0]));

        $price = (new PublicProductPrice())->resolve($product, Carbon::parse('2026-10-01'));

        $this->assertSame(20.0, $price['current']);
        $this->assertFalse($price['is_special']);
    }
}
