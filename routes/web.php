<?php

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

$router->group(['prefix' => 'api/v1'], function() use ($router) {

    $router->group(['prefix' => 'stores'], function() use ($router) {

        // view all store branches with all of its children
        $router->get('/', 'StoreController@index');

        // view one store branch, optionally with all of its children
        $router->get('/{id}', 'StoreController@show');

        // create a store branch
        $router->post('/', 'StoreController@create');

        // update a store branch
        $router->put('/{id}', 'StoreController@update');

        // delete a store branch along with its children
        $router->delete('/{id}', 'StoreController@delete');

        // move a store branch to new parent, along with its children
        $router->put('/{id}/parent/{parentId}', 'StoreController@move');
    });
});
