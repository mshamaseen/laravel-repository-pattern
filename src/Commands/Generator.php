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
    {name : Class (singular) for example User} {--only-view}';

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
        $file = preg_split( " (/|\\\\) ", (string)$this->argument('name')) ?? [];
        $this->repoName = $file[count($file) - 1];
        if($this->option('only-view'))
        {
            $this->makeViewsAndLanguage();
            return null;
        }

        $this->makeRepositoryPatternFiles($file);
    }

    function makeRepositoryPatternFiles($file)
    {

        unset($file[count($file) - 1]);
        $path = implode("\\", $file);

        $model= str_plural(\Config::get('repository.model'));
        $interface= str_plural(\Config::get('repository.interface'));
        $repository= str_plural(\Config::get('repository.repository'));

        $this->generate($path, 'Http\Controllers', 'Controller');
        $this->generate($path, $model, 'Entity');
        $this->generate($path, 'Http\Requests', 'Request');
        $this->generate($path, $interface, 'Interface');
        $this->generate($path, $repository, 'Repository');

        File::append(\Config::get('repository.route_path') . '/web.php', "\n" . 'Route::resource(\'' . strtolower(str_plural($this->repoName)) . "', '".$path."\\".$this->repoName."Controller');");
    }

    function makeViewsAndLanguage()
    {
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'create');
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'edit');
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'index');
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'show');
    }

    /**
     * Get stub content to generate needed files
     *
     * @param string $type determine which stub should choose to get content
     * @return false|string
     */
    protected function getStub($type)
    {
        return file_get_contents(\Config::get('repository.stubs_path') . "/$type.stub");
    }

    /**
     * @param string $path Class path
     * @param string $folder default path to generate in
     * @param string $type define which kind of files should generate
     * @return false
     */
    protected function generate($path, $folder, $type)
    {
        $content = $this->getStub($type);

        if($content === false)
        {
            echo 'file '.$type.".stub is not exist !";
            return false;
        }

        $template = str_replace(
            [
                '{{modelName}}',
                "{{folder}}",
                "{{path}}",
                "{{modelBaseFolderName}}",
                "{{interfaceBaseFolderName}}",
            ],
            [
                $this->repoName,
                str_plural($folder),
                $path,
                str_plural(\Config::get('repository.model','Entity')),
                str_plural(\Config::get('repository.interface','Interface')),
            ],
            $this->getStub($type)
        );

        $folder = str_replace('\\','/',$folder);
        $path = str_replace('\\','/',$path);
        
        switch ($type)
        {
            case 'Entity':
                $filePath = $this->getFolderOrCreate(\Config::get('repository.app_path') . "/{$folder}/{$path}");
                $filePath = rtrim($filePath,'/');
                $filePath .= "/";
                file_put_contents($filePath . "{$this->repoName}.php", $template);
                break;
            case 'create':
            case 'edit':
            case 'index':
            case 'show':
                $filePath = $this->getFolderOrCreate($folder."/".str_plural($path))."/";
                $repoName = lcfirst($type);
                file_put_contents($filePath . $repoName.".blade.php", $template);
            break;
            default:
                $filePath = $this->getFolderOrCreate(\Config::get('repository.app_path') . "/{$folder}/{$path}");
                $filePath = rtrim($filePath,'/');
                $filePath .= "/";
                file_put_contents($filePath . "{$this->repoName}{$type}.php", $template);
        }
        return true;
    }

    /**
     * Check if folder exist
     * @param string $path class path
     * @return string
     */
    public function getFolderOrCreate($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
}
