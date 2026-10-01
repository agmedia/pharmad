<?php

namespace Tests\Feature;

use App\Services\DigitalPriceListInstaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DigitalPriceListInstallerTest extends TestCase
{
    use RefreshDatabase;

    public function test_installer_backfills_only_missing_anchor_values_and_is_idempotent(): void
    {
        $this->insertProduct('EMPTY-ANCHOR', 19.90, null, null);
        $this->insertProduct('CUSTOM-ANCHOR', 25, 17.50, '2025-05-02');

        $installer = app(DigitalPriceListInstaller::class);
        $first = $installer->install();
        $second = $installer->install();

        $this->assertTrue($installer->isInstalled());
        $this->assertSame(1, $first['pricesFilled']);
        $this->assertSame(1, $first['datesFilled']);
        $this->assertSame(0, $second['pricesFilled']);
        $this->assertSame(0, $second['datesFilled']);
        $this->assertDatabaseHas('products', [
            'sku' => 'EMPTY-ANCHOR',
            'anchor_price' => 19.90,
            'anchor_date' => '2026-09-10',
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'CUSTOM-ANCHOR',
            'anchor_price' => 17.50,
            'anchor_date' => '2025-05-02',
        ]);
    }

    private function insertProduct(string $sku, float $price, ?float $anchorPrice, ?string $anchorDate): void
    {
        DB::table('products')->insert([
            'name' => $sku,
            'sku' => $sku,
            'slug' => strtolower($sku),
            'url' => strtolower($sku),
            'price' => $price,
            'anchor_price' => $anchorPrice,
            'anchor_date' => $anchorDate,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
