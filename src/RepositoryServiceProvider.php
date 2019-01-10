<?php

namespace Shamaseen\Repository\Generator;

use Illuminate\Support\ServiceProvider;

/**
 * Class RepositoryServiceProvider.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [];
    protected $providers = [];

    /**
     * RepositoryServiceProvider constructor.
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
    }

    /**
     * Register services.
     */
    public function register()
    {

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return $this->providers;
    }
}
