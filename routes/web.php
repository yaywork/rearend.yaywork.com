<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// //unprotected
$router->group([
    'prefix' => 'v1'
], function ($router) {    
    
    //AUTH
    $router->post('auth/login', 'AuthController@login');
    $router->post('auth/refresh', 'AuthController@refresh');
    
    //USER
    $router->post('user', 'UserController@create');
    $router->get('user/checkUsername/{username}', 'UserController@checkUsername');
    $router->post('user/setAndSendCode', 'UserController@setAndSendCode');
    $router->post('user/verifyCode', 'UserController@verifyCode');
    $router->patch('user/resetPassword', 'UserController@updatePassword');

});


//protected
$router->group([
    'middleware' => 'auth:api',
    'prefix' => 'v1'
], function ($router) {

    //AUTH
    $router->post('auth/logout', 'AuthController@logout');
    $router->get('auth/user', 'AuthController@me');

    //USER
    $router->patch('user/confirm', 'UserController@confirm');

});