<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generator
    {name : Class (singular) for example User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD operations';

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
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        $this->appendToAppServiceProvider($name);
        $this->controller($name);
        $this->model($name);
        $this->request($name);
        $this->repository($name);
        $this->interface($name);

        File::append(base_path('routes/web.php'), "\n".'Route::resource(\'' . str_plural($name) . "', '{$name}Controller');");

    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }

    protected function model($name)
    {
        $pluralName = str_plural($name);
        $modelTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}'
            ],
            [
                $name,
                $pluralName
            ],
            $this->getStub('Model')
        );

        $path = $this->FolderOrNew(app_path('Entities/'.$pluralName)."/");
        file_put_contents($path."{$name}.php", $modelTemplate);
    }

    protected function controller($name)
    {
        $pluralName = str_plural($name);
        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}'
            ],
            [
                $name,
                $pluralName
            ],
            $this->getStub('Controller')
        );

        $path = $this->FolderOrNew(app_path('/Http/Controllers/'.$pluralName)."/");
        file_put_contents($path."{$name}Controller.php", $controllerTemplate);
    }

    protected function request($name)
    {
        $pluralName = str_plural($name);
        $template = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}'
            ],
            [
                $name,
                $pluralName
            ],
            $this->getStub('Request')
        );

        $path = $this->FolderOrNew(app_path("/Http/Requests/{$pluralName}")."/");
        file_put_contents($path."{$name}Request.php", $template);
    }

    protected function interface($name)
    {
        $pluralName = str_plural($name);
        $template = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}'
            ],
            [
                $name,
                $pluralName
            ],
            $this->getStub('Interface')
        );

        $path = $this->FolderOrNew(app_path("/Interfaces/{$pluralName}")."/");
        file_put_contents($path."{$name}Interface.php", $template);
    }

    protected function repository($name)
    {
        $pluralName = str_plural($name);
        $template = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePlural}}'
            ],
            [
                $name,
                $pluralName
            ],
            $this->getStub('Repository')
        );

        $path = $this->FolderOrNew(app_path("/Repositories/{$pluralName}")."/");
        file_put_contents($path."{$name}Repository.php", $template);
    }

    function FolderOrNew($path)
    {
        if(!file_exists($path))
        {
            mkdir($path,0777,true);
        }
        return $path;
    }

    function appendToAppServiceProvider($name)
    {
        $pluralName = str_plural($name);
        $appServerProvider = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $replaceWith = ";\n\nuse App\Interfaces\\".$pluralName."\\".$name."Interface;\nuse App\Repositories\\".$pluralName."\\".$name."Repository;\n";
        $appServerProvider = substr_replace($appServerProvider, $replaceWith, strpos($appServerProvider, ';'), 1);;

        $replaceWith = ";\n        ".'$this->app->bind('.$name.'Interface::class , '.$name.'Repository::class); $1';
        $appServerProvider = preg_replace("/;([^;]+)$/", $replaceWith, $appServerProvider);

        file_put_contents(app_path('Providers/AppServiceProvider.php'),$appServerProvider);
    }
}
