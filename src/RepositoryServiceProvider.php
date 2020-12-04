<?php

namespace Shamaseen\Repository\Generator;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Shamaseen\Repository\Generator\Utility\ContractInterface;

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
     *
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        if (null === $this->app['config']->get('repository')) {
            $this->app['config']->set('repository', require __DIR__ . '/config/repository.php');
        }
        $interfaces = Str::plural(Config::get('repository.interface'));
        $repositories = Str::plural(Config::get('repository.repository'));
        $interface = Config::get('repository.interface');
        $repository = Config::get('repository.repository');

        $contractsFolder = Config::get('repository.app_path') . '/' . $interfaces;

        if (is_dir($contractsFolder)) {
            $directory = new RecursiveDirectoryIterator($contractsFolder);
            $iterator = new RecursiveIteratorIterator($directory);
            $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
            foreach ($regex as $name => $value) {
                $contract = strstr($name, 'app/') ?: strstr($name, 'app\\');
                $contract = rtrim($contract, '.php');

                $contractName = str_replace('/', '\\', ucfirst($contract));

                //replace only first occurance
                $pos = strpos($contractName, $interfaces);
                if ($pos !== false) {
                    $repositoryClass = substr_replace($contractName, $repositories, $pos, strlen($interfaces));
                }

                //replace only last occurance
                $pos = strrpos($repositoryClass, $interface);
                if ($pos !== false) {
                    $repositoryClass = substr_replace($repositoryClass, $repository, $pos, strlen($interface));
                }

                $this->providers[] = $contractName;
                $this->bindings[$contractName] = $repositoryClass;

                if (interface_exists($contractName) && in_array(ContractInterface::class, class_implements($contractName))) {
                    $this->providers[] = $contractName;
                    $this->bindings[$contractName] = $repositoryClass;
                }
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
