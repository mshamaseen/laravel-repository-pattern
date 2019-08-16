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

    //namespaces
    'controllers_folder' => 'Http\Controllers',
    'requests_folder' => 'Http\Requests',

    'languages' => [
        'en',
    ],
];
