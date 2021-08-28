<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ServiceTradeService;
class ServiceTradeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('App\Services\ServiceTradeService', function ($app) {
            return new ServiceTradeService();
          });
    }
}
