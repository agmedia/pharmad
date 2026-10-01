<?php

namespace Tests\Unit;

use App\Services\AnchorPriceCsvImporter;
use PHPUnit\Framework\TestCase;

class AnchorPriceCsvImporterTest extends TestCase
{
    public function test_it_accepts_croatian_and_iso_values(): void
    {
        $importer = new AnchorPriceCsvImporter();

        $this->assertSame(12.34, $importer->parseDecimal('12,34'));
        $this->assertSame(12.34, $importer->parseDecimal('12.34'));
        $this->assertSame(1234.56, $importer->parseDecimal('1.234,56'));
        $this->assertSame(1234.56, $importer->parseDecimal('1,234.56'));
        $this->assertSame('2026-09-10', $importer->parseDate('10.09.2026.'));
        $this->assertSame('2026-09-10', $importer->parseDate('2026-09-10'));
    }

    public function test_it_rejects_non_positive_prices_and_invalid_dates(): void
    {
        $importer = new AnchorPriceCsvImporter();

        $this->assertNull($importer->parseDecimal('0'));
        $this->assertNull($importer->parseDecimal('-1'));
        $this->assertNull($importer->parseDate('31.02.2026.'));
    }
}
