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
     * The repository name.
     *
     * @var string
     */
    protected $repoName;
    /**
     * The repository name space.
     *
     * @var string
     */
    protected $repoNamespace;

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *

     */
    public function handle()
    {
        $file = explode("/", (string)$this->argument('name'));

        $this->repoName = $file[count($file) - 1];
        unset($file[count($file) - 1]);
        $path = implode("/", $file);

        if (count($file) == 0) {
            $this->repoNamespace = '';
        } else {
            $this->repoNamespace = $file[count($file) - 1];
            $this->repoNamespace = "\\".implode("\\", $file);
        }
        $model= str_plural(\Config::get('repository.model'));
        $interface= str_plural(\Config::get('repository.interface'));
        $repository= str_plural(\Config::get('repository.repository'));

        $this->generate($path, \Config::get('repository.controllers_path'), 'Controller');
        $this->generate($path, $model, 'Entity');
        $this->generate($path, \Config::get('repository.requests_path'), 'Request');
        $this->generate($path, $interface, 'Interface');
        $this->generate($path, $repository, 'Repository');

        $this->repoNamespace = stripslashes($this->repoNamespace);
        if($this->repoNamespace)
            $this->repoNamespace = $this->repoNamespace."\\";
        File::append(\Config::get('repository.route_path') . '/web.php', "\n" . 'Route::resource(\'' . strtolower(str_plural($this->repoName)) . "', '{$this->repoNamespace}{$this->repoName}Controller');");

    }

    /**
     * Get stub content to generate needed files
     *
     * @param string $type determine which stub should choose to get content
     * @return false|string
     */
    protected function getStub($type)
    {
        return file_get_contents(\Config::get('repository.resources_path') . "/$type.stub");
    }

    /**
     * @param string $path Class path
     * @param string $folder default path to generate in
     * @param string $type define which kind of files should generate
     */
    protected function generate($path, $folder, $type)
    {
        $template = str_replace(
            [
                '{{modelName}}',
                "{{namespace}}"
            ],
            [
                $this->repoName,
                $this->repoNamespace
            ],
            $this->getStub($type)
        );
        $filePath = $this->checkFolder(\Config::get('repository.app_path') . "/{$folder}/{$path}/");
        if($type == 'Entity')
        {
            file_put_contents($filePath . "{$this->repoName}.php", $template);
        }else
        {
            file_put_contents($filePath . "{$this->repoName}{$type}.php", $template);
        }

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
