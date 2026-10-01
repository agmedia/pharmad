<?php

namespace App\Services;

use App\Models\Back\Catalog\Product\Product;
use App\Models\PriceListExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use XMLWriter;

class DigitalPriceListGenerator
{
    private PriceListSettingsService $settings;
    private PublicProductPrice $prices;

    public function __construct(PriceListSettingsService $settings, PublicProductPrice $prices)
    {
        $this->settings = $settings;
        $this->prices = $prices;
    }

    public function generate(): PriceListExport
    {
        $lock = Cache::lock('digital-price-list-generation', 600);
        if (! $lock->get()) {
            throw new RuntimeException('Generiranje cjenika već je u tijeku.');
        }

        try {
            return $this->generateLocked();
        } finally {
            $lock->release();
        }
    }

    private function generateLocked(): PriceListExport
    {
        $settings = $this->settings->all();
        foreach (['object_type', 'address', 'object_code'] as $required) {
            if (trim((string) ($settings[$required] ?? '')) === '') {
                throw new RuntimeException('Nedostaje obvezna postavka cjenika: '.$required);
            }
        }

        $now = now('Europe/Zagreb');
        $sequence = (int) PriceListExport::query()->max('sequence') + 1;
        $filename = $this->filename($settings, $sequence, $now);
        $directory = storage_path('app/price-lists');
        File::ensureDirectoryExists($directory, 0775, true);
        $temporary = $directory.'/.'.$filename.'.tmp';
        $final = $directory.'/'.$filename;
        $count = 0;

        try {
            $writer = new XMLWriter();
            if (! $writer->openURI($temporary)) throw new RuntimeException('Nije moguće otvoriti privremenu XML datoteku.');
            $writer->setIndent(true);
            $writer->startDocument('1.0', 'UTF-8');
            $writer->startElement('cjenik');
            $writer->writeAttribute('verzija', '1.0');
            $this->element($writer, 'oblik_objekta', $settings['object_type']);
            $this->element($writer, 'adresa', $settings['address']);
            $this->element($writer, 'oznaka_objekta', $settings['object_code']);
            $this->element($writer, 'broj_pohrane', $sequence);
            $this->element($writer, 'generirano', $now->toIso8601String());
            $writer->startElement('proizvodi');

            Product::query()->with(['author', 'priceListAction'])->where('status', 1)->where('price', '>', 0)
                ->orderBy('id')->chunkById(250, function ($products) use ($writer, $now, &$count) {
                    foreach ($products as $product) {
                        if (! $product->anchor_price || ! $product->anchor_date) {
                            throw new RuntimeException('Proizvod '.$product->sku.' nema sidrenu cijenu ili datum.');
                        }
                        $price = $this->prices->resolve($product, $now);
                        $writer->startElement('proizvod');
                        $this->element($writer, 'naziv', $product->name);
                        $this->element($writer, 'sku', $product->sku);
                        $this->element($writer, 'marka', optional($product->author)->title ?: '');
                        if ($product->unit_measure && $product->unit_price) {
                            $this->element($writer, 'jedinica_mjere', $product->unit_measure);
                            $this->element($writer, 'cijena_po_jedinici', $this->money($product->unit_price));
                        }
                        $this->element($writer, 'aktualna_cijena', $this->money($price['current']));
                        $this->element($writer, 'posebni_oblik_prodaje', $price['is_special'] ? 'da' : 'ne');
                        $this->element($writer, 'naziv_posebnog_oblika_prodaje', $price['special_name'] ?: '');
                        $this->element($writer, 'sidrena_cijena', $this->money($product->anchor_price));
                        $this->element($writer, 'referentni_datum', Carbon::parse($product->anchor_date)->format('Y-m-d'));
                        $this->element($writer, 'barkod', $product->ean ?: '');
                        $this->element($writer, 'dostupnost', (float) $product->quantity > 0 ? 'dostupno' : 'nedostupno');
                        $writer->endElement();
                        $count++;
                    }
                });

            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();
            $writer->flush();

            $previous = libxml_use_internal_errors(true);
            $validXml = simplexml_load_file($temporary, 'SimpleXMLElement', LIBXML_NONET);
            $xmlErrors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            if ($validXml === false || $xmlErrors) {
                throw new RuntimeException('Generirani XML nije valjan.');
            }
            $checksum = hash_file('sha256', $temporary);
            if (! rename($temporary, $final)) throw new RuntimeException('XML nije moguće atomski objaviti.');

            try {
                $export = DB::transaction(function () use ($sequence, $filename, $now, $count, $checksum) {
                    return PriceListExport::query()->create([
                        'sequence' => $sequence, 'filename' => $filename, 'path' => 'price-lists/'.$filename,
                        'generated_at' => $now, 'product_count' => $count, 'checksum' => $checksum,
                    ]);
                });
            } catch (\Throwable $e) {
                @unlink($final);
                throw $e;
            }
            try {
                $this->purge((int) $settings['retention_days'], $now);
            } catch (\Throwable $e) {
                report($e);
            }
            return $export;
        } catch (\Throwable $e) {
            if (is_file($temporary)) @unlink($temporary);
            throw $e;
        }
    }

    private function purge(int $days, Carbon $now): void
    {
        $cutoff = $now->copy()->subDays(max(30, $days));
        PriceListExport::query()->where('generated_at', '<', $cutoff)->each(function ($export) {
            $path = storage_path('app/'.$export->path);
            if (is_file($path)) @unlink($path);
            $export->delete();
        });
    }

    private function filename(array $settings, int $sequence, Carbon $now): string
    {
        $slug = fn ($value) => trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)), '-');
        return implode('_', [$slug($settings['object_type']), $slug($settings['address']), $slug($settings['object_code']), str_pad($sequence, 6, '0', STR_PAD_LEFT), $now->format('Ymd_His')]).'.xml';
    }

    private function element(XMLWriter $writer, string $name, $value): void
    {
        $writer->writeElement($name, (string) $value);
    }

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
