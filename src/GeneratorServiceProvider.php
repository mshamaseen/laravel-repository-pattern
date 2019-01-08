<?php

namespace Shamaseen\Repository\Generator;

use Illuminate\Support\ServiceProvider;
use Shamaseen\Repository\Generator\Commands\CrudGenerator;

class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerator::class
            ]);
        }
//        $this->loadRoutesFrom(__DIR__.'/routes.php');
//        $this->loadMigrationsFrom(__DIR__.'/migrations');
//        $this->loadViewsFrom(__DIR__.'/views', 'todolist');
//        $this->publishes([
//            __DIR__.'/views' => base_path('resources/views/wisdmlabs/todolist'),
//        ]);

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }
}
