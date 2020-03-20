<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shanmaseen
 * Date: 10/01/19
 * Time: 09:58 am.
 */
return [
    'app_path' => realpath(__DIR__.'/../app/'),
    'route_path' => realpath('routes/'),
    'resources_path' => realpath('resources'),
    'stubs_path' => realpath('resources').'/stubs/',
    'lang_path' => realpath('resources').'/lang/',
    'config_path' => realpath('config'),

    //relative to app path
    'interface' => 'Interface',
    'model' => 'Entity',
    'repository' => 'Repository',

    //Base extend classes
    'base_controller'=>'Shamaseen\Repository\Generator\Utility\Controller',
    'base_resource'=>'Shamaseen\Repository\Generator\Utility\JsonResource',
    'base_interface'=>'Shamaseen\Repository\Generator\Utility\ContractInterface',
    'base_model'=>'Shamaseen\Repository\Generator\Utility\Entity',
    'base_repository'=>'Shamaseen\Repository\Generator\Utility\AbstractRepository',
    'base_request'=>'Shamaseen\Repository\Generator\Utility\Request',

    //namespaces
    'controllers_folder' => 'Http\Controllers',
    'resources_folder' => 'Http\Resources',
    'requests_folder' => 'Http\Requests',

    'languages' => [
        'en',
    ],
];
