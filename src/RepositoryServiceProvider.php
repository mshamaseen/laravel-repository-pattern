<?php

namespace Shamaseen\Repository\Generator;

use Illuminate\Support\Facades\Config;
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

        if ($this->app['config']->get('repository') === null) {
            $this->app['config']->set('repository', require __DIR__.'/config/repository.php');
        }
        $interfaces= str_plural(Config::get('repository.interface'));
        $repositories= str_plural(Config::get('repository.repository'));
        $interface= Config::get('repository.interface');
        $repository= Config::get('repository.repository');

        $contractsFolder = realpath(__DIR__ . '/../../../../app/' . $interfaces);
        if ($contractsFolder) {
            $directory = new \RecursiveDirectoryIterator($contractsFolder);
            $iterator = new \RecursiveIteratorIterator($directory);
            $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
            foreach ($regex as $name => $value) {
                $contract = explode('app/', $name);
                $contract = explode('.php', $contract[1]);
                $contractName = "App\\" . str_replace('/', '\\', $contract[0]);

                $repositoryClass = str_replace($interfaces, $repositories, $contractName);
                $repositoryClass = str_replace([$interface,'Interface'], $repository, $repositoryClass);

                $this->providers[] = $contractName;
                $this->bindings[$contractName] = $repositoryClass;
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
