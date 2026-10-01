<?php

namespace App\Services;

use App\Models\Back\Catalog\Product\Product;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AnchorPriceCsvImporter
{
    private const REQUIRED = ['sku', 'sidrena_cijena', 'referentni_datum'];

    public function import(UploadedFile $file, ?int $userId = null): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $first = fgets($handle);
        if ($first === false) {
            return $this->result([], [['redak' => 1, 'sku' => '', 'greska' => 'Datoteka je prazna.']]);
        }
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($handle);
        $headers = array_map([$this, 'header'], fgetcsv($handle, 0, $delimiter));
        $missing = array_diff(self::REQUIRED, $headers);
        if ($missing) {
            fclose($handle);
            return $this->result([], [['redak' => 1, 'sku' => '', 'greska' => 'Nedostaju stupci: '.implode(', ', $missing)]]);
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $data = array_pad($data, count($headers), null);
            $rows[] = ['line' => $line, 'data' => array_combine($headers, array_slice($data, 0, count($headers)))];
        }
        fclose($handle);

        $counts = array_count_values(array_map(fn ($row) => trim((string) ($row['data']['sku'] ?? '')), $rows));
        $updated = [];
        $errors = [];
        foreach ($rows as $row) {
            $sku = trim((string) ($row['data']['sku'] ?? ''));
            if ($sku === '' || ($counts[$sku] ?? 0) > 1) {
                $errors[] = $this->error($row, $sku === '' ? 'SKU je obvezan.' : 'Duplikat SKU-a u datoteci.');
                continue;
            }
            $price = $this->parseDecimal($row['data']['sidrena_cijena'] ?? null);
            $date = $this->parseDate($row['data']['referentni_datum'] ?? null);
            $measure = trim((string) ($row['data']['jedinica_mjere'] ?? '')) ?: null;
            $unitPrice = $this->parseDecimal($row['data']['cijena_po_jedinici'] ?? null, true);
            if (! $price || ! $date || (($measure === null) xor ($unitPrice === null))) {
                $errors[] = $this->error($row, 'Neispravna cijena/datum ili jedinica i jedinična cijena nisu unesene zajedno.');
                continue;
            }
            $product = Product::query()->where('sku', $sku)->first();
            if (! $product) {
                $errors[] = $this->error($row, 'Nepoznat SKU.');
                continue;
            }
            try {
                DB::transaction(function () use ($product, $price, $date, $measure, $unitPrice, $userId) {
                    $old = $product->only(['anchor_price', 'anchor_date', 'unit_measure', 'unit_price']);
                    $product->update(['anchor_price' => $price, 'anchor_date' => $date, 'unit_measure' => $measure, 'unit_price' => $unitPrice]);
                    if ($userId) {
                        DB::table('history_log')->insert([
                            'user_id' => $userId, 'type' => 'change', 'target' => 'product', 'target_id' => $product->id,
                            'title' => 'CSV izmjena sidrene cijene: '.$product->name,
                            'changes' => 'Sidreni podaci ažurirani CSV uvozom.',
                            'old_model' => json_encode($old), 'new_model' => json_encode($product->fresh()->only(array_keys($old))),
                            'badge' => 0, 'comment' => 'CSV sidrene cijene', 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                });
                $updated[] = $sku;
            } catch (\Throwable $e) {
                report($e);
                $errors[] = $this->error($row, 'Greška pri spremanju retka.');
            }
        }

        return $this->result($updated, $errors);
    }

    private function header($value): string
    {
        return strtolower(trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"));
    }

    public function parseDecimal($value, bool $nullable = false): ?float
    {
        $value = trim((string) $value);
        if ($value === '' && $nullable) return null;
        $value = str_replace(' ', '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $decimal = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $value = str_replace($decimal === ',' ? '.' : ',', '', $value);
            $value = str_replace($decimal, '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }
        return is_numeric($value) && (float) $value > 0 ? round((float) $value, 4) : null;
    }

    public function parseDate($value): ?string
    {
        foreach (['Y-m-d', 'd.m.Y.', 'd.m.Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim((string) $value));
                if ($date && $date->format($format) === trim((string) $value)) return $date->format('Y-m-d');
            } catch (\Throwable $e) {}
        }
        return null;
    }

    private function error(array $row, string $message): array
    {
        return ['redak' => $row['line'], 'sku' => $row['data']['sku'] ?? '', 'greska' => $message];
    }

    private function result(array $updated, array $errors): array
    {
        return ['updated' => $updated, 'errors' => $errors, 'updated_count' => count($updated), 'error_count' => count($errors)];
    }
}
