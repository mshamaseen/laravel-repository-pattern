<?php

namespace Shamaseen\Repository\Generator;

use Illuminate\Support\ServiceProvider;

use Shamaseen\Repository\Generator\Commands\RepositoryGenerator;

/**
 * Class GeneratorServiceProvider
 * @package Shamaseen\Repository\Generator
 */
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
                RepositoryGenerator::class
            ]);
        }
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
