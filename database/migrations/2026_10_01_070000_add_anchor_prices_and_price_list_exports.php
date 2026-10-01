<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAnchorPricesAndPriceListExports extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('products', 'anchor_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('anchor_price', 15, 4)->nullable()->after('price');
            });
        }
        if (! Schema::hasColumn('products', 'anchor_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->date('anchor_date')->nullable()->after('anchor_price');
            });
        }
        if (! Schema::hasColumn('products', 'unit_measure')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('unit_measure', 50)->nullable()->after('anchor_date');
            });
        }
        if (! Schema::hasColumn('products', 'unit_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('unit_price', 15, 4)->nullable()->after('unit_measure');
            });
        }

        DB::table('products')->whereNull('anchor_price')->update([
            'anchor_price' => DB::raw('price'),
        ]);
        DB::table('products')->whereNull('anchor_date')->update([
            'anchor_date' => '2026-09-10',
        ]);

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
        }
    }

    public function down()
    {
        Schema::dropIfExists('price_list_exports');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['anchor_price', 'anchor_date', 'unit_measure', 'unit_price']);
        });
    }
}
