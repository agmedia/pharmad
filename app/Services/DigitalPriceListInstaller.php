<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DigitalPriceListInstaller
{
    public function install(): array
    {
        if (! Schema::hasTable('products')) {
            throw new \RuntimeException('Tablica products ne postoji.');
        }

        $added = [];
        $columns = [
            'anchor_price' => function (Blueprint $table) {
                $table->decimal('anchor_price', 15, 4)->nullable()->after('price');
            },
            'anchor_date' => function (Blueprint $table) {
                $table->date('anchor_date')->nullable()->after('anchor_price');
            },
            'unit_measure' => function (Blueprint $table) {
                $table->string('unit_measure', 50)->nullable()->after('anchor_date');
            },
            'unit_price' => function (Blueprint $table) {
                $table->decimal('unit_price', 15, 4)->nullable()->after('unit_measure');
            },
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('products', $name)) {
                Schema::table('products', $definition);
                $added[] = $name;
            }
        }

        $pricesFilled = DB::table('products')->whereNull('anchor_price')->update([
            'anchor_price' => DB::raw('price'),
        ]);
        $datesFilled = DB::table('products')->whereNull('anchor_date')->update([
            'anchor_date' => '2026-09-10',
        ]);

        $archiveCreated = false;
        if (! Schema::hasTable('price_list_exports')) {
            Schema::create('price_list_exports', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sequence')->unique();
                $table->string('filename')->unique();
                $table->string('path');
                $table->timestamp('generated_at');
                $table->unsignedInteger('product_count')->default(0);
                $table->char('checksum', 64);
                $table->timestamps();
            });
            $archiveCreated = true;
        }

        return compact('added', 'pricesFilled', 'datesFilled', 'archiveCreated');
    }

    public function isInstalled(): bool
    {
        foreach (['anchor_price', 'anchor_date', 'unit_measure', 'unit_price'] as $column) {
            if (! Schema::hasColumn('products', $column)) {
                return false;
            }
        }

        return Schema::hasTable('price_list_exports');
    }
}
