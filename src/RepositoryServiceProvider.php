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
        $contractsFolder = realpath(__DIR__ . '/../../../../app/Contracts');
        $directory = new \RecursiveDirectoryIterator($contractsFolder);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
        foreach ($regex as $name => $value) {

            if (strpos($name, 'BaseContract') === false) {
                $contract = explode('app/', $name);
                $contract = explode('.php', $contract[1]);
                $contractName = "App\\" . str_replace('/', '\\', $contract[0]);
                $repository = str_replace('Contracts', 'Repositories', $contractName);
                $repository = str_replace('Contract', 'Repository', $repository);
                $this->providers[] = $contractName;
                $this->bindings[$contractName] = $repository;
            }
        }
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
