<?php

namespace Shamaseen\Repository\Generator;

use Illuminate\Support\ServiceProvider;

use Shamaseen\Repository\Generator\Commands\Generator;

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
                Generator::class
            ]);
        }

        $this->publishes([
            __DIR__.'/config' => realpath('config'),
        ],'repository-config');

        if ($this->app['config']->get('repository') === null) {
            $this->app['config']->set('repository', require __DIR__.'/config/repository.php');
        }
        $this->mergeConfigFrom(__DIR__.'/config/repository.php', 'repository-config');

        $this->publishes([
            __DIR__ . '/stubs' => \Config::get('repository.resources_path',realpath(__DIR__.'/../../../../resources/'))."/stubs/",
        ],'repository-stub');
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
