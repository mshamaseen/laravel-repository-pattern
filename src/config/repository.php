<?php
/**
 * Created by PhpStorm.
 * User: shanmaseen
 * Date: 10/01/19
 * Time: 09:58 ص
 */
return [
    'app_path' => realpath(__DIR__.'/../app/'),
    'route_path' => realpath(__DIR__.'/../routes/'),
    'resources_path' => realpath(__DIR__.'/../resources/'),

    //relative to app path
    'interfaces_path' => realpath(__DIR__.'/../app/').'/Interfaces/',
    'models_path' => realpath(__DIR__.'/../app/').'/Entities/',
    'controllers_path' => realpath(__DIR__.'/../app/').'/Http/Controllers/',
    'repositories_path' => realpath(__DIR__.'/../app/').'/Repositories/',
    'requests_path' => realpath(__DIR__.'/../app/').'/Http/Requests/',

];
