<?php

namespace Shamaseen\RepositoryGenerateor;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
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
//        $this->app->make('shamaseen\repositoryGenerateor\src\commands');
    }
}
