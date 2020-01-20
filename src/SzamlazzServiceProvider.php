<?php

namespace Gbl\Szamlazz;

use Illuminate\Support\ServiceProvider;

class SzamlazzServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        $this->publishes([
            __DIR__. '/config/gbl.php' => config_path('gbl.php'),
        ]);
    }
    public function register()
    {
        $this->mergeConfigFrom(__DIR__. '/config/gbl.php', 'gbl');
    }
}