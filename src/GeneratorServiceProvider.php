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
        $this->publishes([
            __DIR__.'/config' => realpath(__DIR__.'/../../../../../config'),
            __DIR__.'/stubs' => \Config::get('repository.resources_path')."stubs/",
        ]);
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
