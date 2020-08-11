<?php
/**
 * Created by PhpStorm.
 * User: shanmaseen
 * Date: 10/08/19
 * Time: 04:48 م
 */

namespace Shamaseen\Repository\Generator\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Remover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:repository
    {name : Class (singular) for example User} {--only-view}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove repository files';

    /**
     * The repository name.
     *
     * @var string
     */
    protected $repoName;

    /**
     * Create a new command instance.
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
        $file = preg_split(' (/|\\\\) ', (string)$this->argument('name')) ?? [];

        if (!$file) {
            return 'Something wrong with the inputs !';
        }

        $this->repoName = $file[count($file) - 1];

        unset($file[count($file) - 1]);
        $path = implode('\\', $file);

        if (!$this->confirm('This will delete ' . $this->repoName . ' files and folder, Do you want to continue ?')) {
            return false;
        }

        $model = Str::plural(Config::get('repository.model'));
        $interface = Str::plural(Config::get('repository.interface'));
        $repository = Str::plural(Config::get('repository.repository'));
        $controllerFolder = Config::get('repository.controllers_folder');
        $requestFolder = Config::get('repository.requests_folder');
        $resourceFolder = Config::get('repository.resources_folder');

        $this->remove('Entity', $model, $path);
        $this->remove('Controller', $controllerFolder, $path);
        $this->remove('Resource', $resourceFolder, $path);
        $this->remove('Request', $requestFolder, $path);
        $this->remove('Repository', $repository, $path);
        $this->remove('Interface', $interface, $path);
        return true;
    }

    public function remove($type, $folder, $relativePath)
    {
        $folder = str_replace('\\', '/', $folder);
        $relativePath = str_replace('\\', '/', $relativePath);

        switch ($type) {
            case 'Entity':
                $filePath = Config::get('repository.app_path') . "/{$folder}/{$relativePath}/";
                $fileName = "{$this->repoName}.php";
                break;
            case 'Controller':
            case 'Request':
            case 'Resource':
            case 'Repository':
            case 'Interface':
            default:
                $filePath = Config::get('repository.app_path') . "/{$folder}/{$relativePath}/";
                $fileName = "{$this->repoName}{$type}.php";
        }
        if (!is_file($filePath . $fileName)) {
            $this->warn($filePath . $fileName . ' is not a valid file');
            return false;
        }

        unlink($filePath . $fileName);
        if (!(new \FilesystemIterator($filePath))->valid()) {
            rmdir($filePath);
        }
        return true;
    }
}
