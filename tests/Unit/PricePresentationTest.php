<?php

namespace Tests\Unit;

use Tests\TestCase;

class PricePresentationTest extends TestCase
{
    public function test_equal_current_and_anchor_prices_are_both_rendered(): void
    {
        $product = new class {
            public $price = 12.60;
            public $anchor_price = 12.60;
            public $anchor_date = '2026-09-10';
            public $main_price_text = '12,60 €';
            public $main_special_text = '12,60 €';
            public $main_anchor_price_text = '12,60 €';
            public function special() { return 12.60; }
        };

        $html = view('front.catalog.partials.prices', compact('product'))->render();

        $this->assertStringContainsString('Cijena', $html);
        $this->assertStringNotContainsString('Aktualna cijena', $html);
        $this->assertStringContainsString('Cijena na 10.09.2026.', $html);
        $this->assertSame(2, substr_count($html, '12,60 €'));
    }

    public function test_sale_renders_action_nc30_and_anchor_as_separate_rows(): void
    {
        $product = new class {
            public $price = 20.00;
            public $anchor_price = 18.00;
            public $anchor_date = '2026-09-10';
            public $main_price_text = '20,00 €';
            public $main_special_text = '15,00 €';
            public $main_anchor_price_text = '18,00 €';
            public function special() { return 15.00; }
        };

        $html = view('front.catalog.partials.prices', compact('product'))->render();

        $this->assertStringContainsString('Akcijska cijena', $html);
        $this->assertStringContainsString('NC30', $html);
        $this->assertStringContainsString('Cijena na 10.09.2026.', $html);
        $this->assertStringContainsString('15,00 €', $html);
        $this->assertStringContainsString('20,00 €', $html);
        $this->assertStringContainsString('18,00 €', $html);
    }
}
