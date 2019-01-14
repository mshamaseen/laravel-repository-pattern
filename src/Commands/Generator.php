<?php

namespace Shamaseen\Repository\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class RepositoryGenerator
 * @package Shamaseen\Repository\Generator\Commands
 */
class Generator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository
    {name : Class (singular) for example User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create repository generator';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $file = explode("/", (string)$this->argument('name'));

        $name = $file[count($file) - 1];
        unset($file[count($file) - 1]);
        $path = implode("/", $file);

        $this->generate($name, $path, \Config::get('repository.controllers_path'), 'Controller');
        $this->generate($name, $path, \Config::get('repository.models_path'), 'Model');
        $this->generate($name, $path, \Config::get('repository.requests_path'), 'Request');
        $this->generate($name, $path, \Config::get('repository.interfaces_path'), 'Interface');
        $this->generate($name, $path, \Config::get('repository.repositories_path'), 'Repository');

        File::append(\Config::get('repository.route_path').'web.php', "\n" . 'Route::resource(\'' . str_plural($name) . "', '{$name}Controller');");

    }

    /**
     * Get stub content to generate needed files
     *
     * @param string $type  determine which stub should choose to get content
     * @return false|string
     */
    protected function getStub($type)
    {
        return file_get_contents(\Config::get('repository.resources_path')."/stubs/$type.stub");
    }

    /**
     * @param string $name Class name
     * @param string $path Class path
     * @param string $folder default path to generate in
     * @param string $type define which kind of files should generate
     */
    protected function generate($name, $path, $folder, $type)
    {
        $namespace = str_replace("/", "\\", $path);
        $template = str_replace(
            [
                '{{modelName}}',
                "{{modelNamePlural}}"
            ],
            [
                $name,
                $namespace . "\\" . str_plural($name)
            ],
            $this->getStub($type)
        );

        $path = $this->checkFolder(\Config::get('repository.app_path').$folder.$path."/");
        file_put_contents($path . "{$name}{$type}.php", $template);

    }

    /**
     * Check if folder exist
     * @param string $path class path
     * @return string
     */
    public function checkFolder($path)
    {

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
}
