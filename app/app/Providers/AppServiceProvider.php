<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\StoreFinder\StoreFinder;
use App\Services\StoreFinder\LocalStoreFinder;
use App\Services\StoreFinder\GooglePlacesStoreFinder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(StoreFinder::class, function ($app) {
        // env でも良いですが、将来 config キャッシュするなら config 化推奨
        $driver = env('STORE_FINDER', 'local'); // または config('services.store_finder', 'local')

        if ($driver === 'google') {
            return new GooglePlacesStoreFinder();
        }
        return new LocalStoreFinder();
    });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
