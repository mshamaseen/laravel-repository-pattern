<?php

namespace Shamaseen\Repository\Generator\Commands;

use Config;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Shamaseen\Repository\Generator\Forms\FormGenerator;

/**
 * Class RepositoryGenerator.
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
    private $FormGenerator;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->FormGenerator = new FormGenerator();
    }

    /**
     * Execute the console command.
     *
     * @throws ReflectionException
     */
    public function handle()
    {
        $file = preg_split(' ([/\\\]) ', (string)$this->argument('name')) ?? [];

        if (!$file) {
            return 'Something wrong with the inputs !';
        }

        $this->repoName = $file[count($file) - 1];

        unset($file[count($file) - 1]);
        $path = implode('\\', $file);

        if ($this->option('only-view')) {
            $this->makeViewsAndLanguage($path);
            $this->dumpAutoload();
            return true;
        }

        return $this->makeRepositoryPatternFiles($path);
    }

    public function makeRepositoryPatternFiles($path)
    {
        $model = Str::plural(Config::get('repository.model', 'Entity'));
        $interface = Str::plural(Config::get('repository.interface', 'Interface'));
        $repository = Str::plural(Config::get('repository.repository', 'Repository'));
        $controller = Config::get('repository.controllers_folder', 'Http\Controllers');
        $request = Config::get('repository.requests_folder', 'Http\Requests');
        $resource = Config::get('repository.resources_folder', 'Http\Resources');

        $base = "Shamaseen\Repository\Generator\Utility";
        $modelBase = Config::get('repository.base_model', "{$base}\Entity");
        $interfaceBase = Config::get('repository.base_interface', "{$base}\ContractInterface");
        $repositoryBase = Config::get('repository.base_repository', "{$base}\AbstractRepository");
        $controllerBase = Config::get('repository.base_controller', "{$base}\Controller");
        $requestBase = Config::get('repository.base_request', "{$base}\Request");
        $resourceBase = Config::get('repository.base_resource', "{$base}\JsonResource");

        $this->generate($path, $controller, 'Controller','', $controllerBase);
        $this->generate($path, $resource, 'Resource', '',$resourceBase);
        $this->generate($path, $model, 'Entity', '',$modelBase);
        $this->generate($path, $request, 'Request', '',$requestBase);
        $this->generate($path, $interface, 'Interface','', $interfaceBase);
        $this->generate($path, $repository, 'Repository', '',$repositoryBase);

     //append routes is not desired anymore
//        $webFile = Config::get('repository.route_path') . '/web.php';
//        $apiFile = Config::get('repository.route_path') . '/api.php';
//        $pluralName = strtolower(Str::plural($this->repoName));
//        $controllerPath = $path . '\\' . $this->repoName . 'Controller';
//        $webContent = "\nRoute::resource('{$pluralName}', '{$controllerPath}');";
//        $webFileContent = str_replace($webContent, '', file_get_contents($webFile));
//        $apiFileContent = str_replace($webContent, '', file_get_contents($apiFile));
//        File::put($webFile, $webFileContent);
//        File::put($apiFile, $apiFileContent);
//        File::append($webFile, $webContent);
//        File::append($apiFile, $webContent);

        $this->dumpAutoload();

        return true;
    }

    /**
     * @param $path
     *
     * @throws ReflectionException
     */
    public function makeViewsAndLanguage($path)
    {
        $entity = $this->getEntity($path);

        $createHtml = '';
        $editHtml = '';
        if ($entity instanceof Model) {
            $createHtml = $this->FormGenerator->generateForm($entity);
            $editHtml = $this->FormGenerator->generateForm($entity, 'put');
        } else {
            $message = "There is no entity for {$this->repoName}, 
                        do you want to continue (this will disable form generator) ?";
            if (!$this->confirm($message)) {
                echo 'Dispatch ..';
                die;
            }
        }
        $repositoryName = lcfirst($this->repoName);
        $viewsPath = Config::get('repository.resources_path') . '/views';
        $languagePath = Config::get('repository.lang_path');

        foreach (Config::get('repository.languages') as $lang) {
            $this->generate($repositoryName, "{$languagePath}{$lang}", 'lang');
        }

        $this->generate($repositoryName, $viewsPath, 'create', $createHtml);
        $this->generate($repositoryName, $viewsPath, 'edit', $editHtml);
        $this->generate($repositoryName, $viewsPath, 'index');
        $this->generate($repositoryName, $viewsPath, 'show');
    }

    /**
     * @param $path
     *
     * @return bool|Model|object
     * @throws ReflectionException
     *
     */
    public function getEntity($path)
    {
        $myClass = 'App\Entities\\' . $path . '\\' . $this->repoName;
        if (!class_exists($myClass)) {
            return false;
        }

        $reflect = new ReflectionClass($myClass);

        return $reflect->newInstance();
    }

    /**
     * Get stub content to generate needed files.
     *
     * @param string $type determine which stub should choose to get content
     *
     * @return false|string
     */
    protected function getStub($type)
    {
        return file_get_contents(Config::get('repository.stubs_path') . "/$type.stub");
    }

    /**
     * @param string $path Class path
     * @param string $folder default path to generate in
     * @param string $type define which kind of files should generate
     * @param string $form
     * @param string $base
     *
     * @return bool
     */
    protected function generate($path, $folder, $type, $form = '', $base = '')
    {
        $path = $path ? '\\' . $path : '';
        $content = $this->getStub($type);

        if (false === $content) {
            echo 'file ' . $type . '.stub is not exist !';

            return false;
        }

        $template = str_replace(
            [
                '{{base}}',
                '{{modelName}}',
                '{{lcPluralModelName}}',
                '{{folder}}',
                '{{path}}',
                '{{modelBaseFolderName}}',
                '{{interfaceBaseFolderName}}',
                '{{form}}',
            ],
            [
                $base,
                $this->repoName,
                Str::plural(lcfirst($this->repoName)),
                Str::plural($folder),
                $path,
                Str::plural(Config::get('repository.model', 'Entity')),
                Str::plural(Config::get('repository.interface', 'Interface')),
                $form,
            ],
            $content
        );

        $folder = str_replace('\\', '/', $folder);
        $path = str_replace('\\', '/', $path);
        $this->type($type, $folder, $path, $template);

        return true;
    }

    /**
     * Check if folder exist.
     *
     * @param string $path class path
     *
     * @return string
     */
    public function getFolderOrCreate($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    /**
     * @param string $path Class path
     * @param string $folder default path to generate in
     * @param string $type define which kind of files should generate
     * @param string $template temple file
     *
     */
    private function type($type, $folder, $path, $template)
    {
        switch ($type) {
            case 'Entity':
                $filePath = $this->getFolderOrCreate(Config::get('repository.app_path') . "/{$folder}/{$path}");
                $filePath = rtrim($filePath, '/');
                $content = "{$filePath}/{$this->repoName}.php";

                break;
            case 'Controller':
            case 'Resource':
            case 'Request':
            case 'Repository':
            case 'Interface':
                $filePath = $this->getFolderOrCreate(Config::get('repository.app_path') . "/{$folder}/{$path}");
                $filePath = rtrim($filePath, '/');
                $content = "{$filePath}/{$this->repoName}{$type}.php";
                break;
            case 'create':
            case 'edit':
            case 'index':
            case 'show':
                $filePath = $this->getFolderOrCreate($folder . '/' . Str::plural($path)) . '/';
                $repoName = lcfirst($type);
                $content = $filePath . $repoName . '.blade.php';
                break;
            default:
                $filePath = $this->getFolderOrCreate($folder) . '/';
                $repoName = lcfirst($this->repoName);
                $content = $filePath . $repoName . '.php';
        }

        if (is_dir($filePath) && file_exists($content)) {
            // Ask to replace exiting file
            if (!$this->confirm("This file, {$content} already exit, do you want to replace?")) {
                $this->line('File Not Replaced');
                return;
            }
        }

        file_put_contents($content, $template);
    }

    function dumpAutoload()
    {
        shell_exec('composer dump-autoload');
    }
}

