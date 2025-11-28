<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\StoreFinder\StoreFinder;
use App\Services\StoreFinder\LocalStoreFinder;
use App\Services\StoreFinder\GooglePlacesStoreFinder;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {  
        $this->app->bind(\App\Services\StoreFinder\StoreFinder::class, function () {
            $driver = config('services.store_finder', env('STORE_FINDER', 'local'));
            if ($driver === 'google') {
                $key = config('services.google.maps_key') ?? config('services.google.places_key');
                return new \App\Services\StoreFinder\GooglePlacesStoreFinder($key);
            }
            return new \App\Services\StoreFinder\LocalStoreFinder();
        });
    }


    public function boot(): void
    {
        //
    }
}
