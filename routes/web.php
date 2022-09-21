<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return 'OK 200';
});

Route::get('apple-app-site-association', function () {
    $obj = [
        'applinks' => [
            'apps' => [],
            'details' => [
                [
                    'appID' => 'DHG3RK7L57.com.akiddie.flok',
                    'paths' => [ '*' ],
                ],
            ],
        ],
    ];
    return response()->json($obj);
});
