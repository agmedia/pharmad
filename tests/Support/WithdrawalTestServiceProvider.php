<?php

namespace Tests\Support;

use Illuminate\Support\ServiceProvider;

// Isolate this module from unrelated catalog queries in AppServiceProvider/layouts.
class WithdrawalTestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['view']->getFinder()->prependLocation(base_path('tests/Fixtures/views'));
    }
}
