<?php

namespace Gbl\Szamlazz\App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class SzamlazzServiceProvider extends ServiceProvider
{
    /**
     *
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        $this->publishes([
            __DIR__ . '/config/gbl.php' => config_path('gbl.php'),
        ]
        );
    }

    /**
     *
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/gbl.php', 'gbl');

        $this->app->bind(Client::class, function ($app) {
            return new Client();
        });

//        $this->app->singleton(Client::class, function ()
//        {
//            return new Client();
//        }
//        );
    }
}