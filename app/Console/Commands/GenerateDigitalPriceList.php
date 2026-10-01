<?php

namespace App\Console\Commands;

use App\Services\DigitalPriceListGenerator;
use Illuminate\Console\Command;

class GenerateDigitalPriceList extends Command
{
    protected $signature = 'price-list:generate';
    protected $description = 'Generira i arhivira javni XML digitalni cjenik';

    public function handle(DigitalPriceListGenerator $generator): int
    {
        try {
            $export = $generator->generate();
            $this->info("Generiran {$export->filename} ({$export->product_count} proizvoda). ");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
