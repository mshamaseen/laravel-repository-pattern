<?php

namespace Shamaseen\Repository\Generator;

use Config;
use Illuminate\Support\ServiceProvider;
use Shamaseen\Repository\Generator\Commands\Generator;
use Shamaseen\Repository\Generator\Commands\Remover;

/**
 * Class GeneratorServiceProvider.
 */
class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Generator::class,
                Remover::class
            ]);
        }

        $this->publishes([
            __DIR__.'/config' => realpath('config'),
        ], 'repository-generator');

        if (null === $this->app['config']->get('repository')) {
            $this->app['config']->set('repository', require __DIR__.'/config/repository.php');
        }
        $this->mergeConfigFrom(__DIR__.'/config/repository.php', 'repository-config');
        $resourcesPath = realpath(__DIR__.'/../../../../resources/');
        $stubPath = realpath(__DIR__.'/../stubs');
        $langPath = Config::get('repository.lang_path').'/en';
        $this->publishes([
            $stubPath => Config::get('repository.resources_path', $resourcesPath),
            __DIR__.'/lang' => $langPath,
        ], 'repository-stub');
    }

    /**
     * Register services.
     */
    public function register()
    {
    }
}
