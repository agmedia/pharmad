<?php

namespace Tests\Feature;

use App\Services\AnchorPriceCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnchorPriceCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_valid_rows_and_reports_unknown_duplicate_and_invalid_rows(): void
    {
        foreach (['GOOD-1', 'DUP-1', 'BAD-1'] as $index => $sku) {
            DB::table('products')->insert([
                'name' => 'Proizvod '.$sku,
                'sku' => $sku,
                'slug' => 'proizvod-'.($index + 1),
                'url' => 'proizvod-'.($index + 1),
                'price' => 20,
                'anchor_price' => 20,
                'anchor_date' => '2026-09-10',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $csv = implode("\n", [
            'sku;sidrena_cijena;referentni_datum;jedinica_mjere;cijena_po_jedinici',
            'GOOD-1;12,34;10.09.2026.;100 ml;123,40',
            'DUP-1;10,00;2026-09-10;;',
            'DUP-1;11,00;2026-09-10;;',
            'UNKNOWN;9,99;2026-09-10;;',
            'BAD-1;8,99;31.02.2026.;;',
        ]);

        $report = app(AnchorPriceCsvImporter::class)->import(
            UploadedFile::fake()->createWithContent('sidrene-cijene.csv', $csv),
            42
        );

        $this->assertSame(1, $report['updated_count']);
        $this->assertSame(4, $report['error_count']);
        $this->assertSame(['GOOD-1'], $report['updated']);
        $this->assertDatabaseHas('products', [
            'sku' => 'GOOD-1',
            'anchor_price' => 12.34,
            'anchor_date' => '2026-09-10',
            'unit_measure' => '100 ml',
            'unit_price' => 123.40,
        ]);
        $this->assertDatabaseHas('products', ['sku' => 'DUP-1', 'anchor_price' => 20]);
        $this->assertDatabaseHas('history_log', [
            'user_id' => 42,
            'target' => 'product',
            'title' => 'CSV izmjena sidrene cijene: Proizvod GOOD-1',
        ]);
    }
}
