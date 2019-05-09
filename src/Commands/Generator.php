<?php

namespace Shamaseen\Repository\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Shamaseen\Repository\Generator\Forms\FormGenerator;

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
    private $FormGenerator;

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->FormGenerator = new FormGenerator();
    }

    /**
     * Execute the console command.
     *
     * @throws \ReflectionException
     */
    public function handle()
    {
        $file = preg_split( " (/|\\\\) ", (string)$this->argument('name')) ?? [];

        if(!$file) return "Something wrong with the inputs !";

        $this->repoName = $file[count($file) - 1];

        unset($file[count($file) - 1]);
        $path = implode("\\", $file);

        if($this->option('only-view'))
        {
            $this->makeViewsAndLanguage($path);
            return null;
        }

        $this->makeRepositoryPatternFiles($path);
    }

    function makeRepositoryPatternFiles($path)
    {
        $model= str_plural(\Config::get('repository.model'));
        $interface= str_plural(\Config::get('repository.interface'));
        $repository= str_plural(\Config::get('repository.repository'));

        $this->generate($path, \Config::get('repository.controllers_folder'), 'Controller');
        $this->generate($path, $model, 'Entity');
        $this->generate($path, \Config::get('repository.requests_folder'), 'Request');
        $this->generate($path, $interface, 'Interface');
        $this->generate($path, $repository, 'Repository');

        File::append(\Config::get('repository.route_path') . '/web.php', "\n" . 'Route::resource(\'' . strtolower(str_plural($this->repoName)) . "', '".$path."\\".$this->repoName."Controller');");
    }

    /**
     * @param $path
     * @throws \ReflectionException
     */
    function makeViewsAndLanguage($path)
    {
        $entity = $this->getEntity($path);

        $createHtml = '';
        $editHtml = '';
        if($entity instanceof Model)
        {
            $createHtml = $this->FormGenerator->generateForm($entity);
            $editHtml = $this->FormGenerator->generateForm($entity,'put');
        }
        else
        {
            if(!$this->confirm('There is no entity for '.$this->repoName.", do you want to continue (this will disable form generator) ?"))
            {
                echo "Dispatch ..";
                die;
            }
        }

        foreach (\Config::get('repository.languages') as $lang)
        {
            $this->generate(lcfirst($this->repoName),\Config::get('repository.lang_path')."/{$lang}" , 'lang');
        }
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'create',$createHtml);
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'edit',$editHtml);
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'index');
        $this->generate(lcfirst($this->repoName),\Config::get('repository.resources_path')."/views" , 'show');
    }

    /**
     * @param $path
     * @return bool|Model|object
     * @throws \ReflectionException
     */
    function getEntity($path)
    {
        $myClass = 'App\Entities\\'.$path."\\".$this->repoName;
        if(!class_exists($myClass))
            return false;

        $refl = new ReflectionClass($myClass);

        return $refl->newInstance();
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
     * @param string $form
     * @return bool
     */
    protected function generate($path, $folder, $type,$form ='')
    {
        $path = $path ? "\\".$path : "";
        $content = $this->getStub($type);

        if($content === false)
        {
            echo 'file '.$type.".stub is not exist !";
            return false;
        }

        $template = str_replace(
            [
                '{{modelName}}',
                '{{lcPluralModelName}}',
                "{{folder}}",
                "{{path}}",
                "{{modelBaseFolderName}}",
                "{{interfaceBaseFolderName}}",
                "{{form}}",
            ],
            [
                $this->repoName,
                str_plural(lcfirst($this->repoName)),
                str_plural($folder),
                $path,
                str_plural(\Config::get('repository.model','Entity')),
                str_plural(\Config::get('repository.interface','Interface')),
                $form
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
            case 'Controller':
            case 'Request':
            case 'Repository':
            case 'Interface':
                $filePath = $this->getFolderOrCreate(\Config::get('repository.app_path') . "/{$folder}/{$path}");
                $filePath = rtrim($filePath,'/');
                $filePath .= "/";
                file_put_contents($filePath . "{$this->repoName}{$type}.php", $template);
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
                $filePath = $this->getFolderOrCreate($folder)."/";
                $repoName = lcfirst($this->repoName);
                file_put_contents($filePath . $repoName.".php", $template);
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
