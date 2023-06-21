<?php

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

$route_prefix = config('module.Print.route_prefix', '');
$route_url_prefix = $route_prefix ? $route_prefix . '/' : '';
$route_name_prefix = $route_prefix ? $route_prefix . '.' : '';

Route::prefix("{$route_url_prefix}print")->name("page.{$route_name_prefix}print.")->group(function () {
    Route::get('/', 'PrintController@pagePrint')->name('index');
    Route::get('/design', 'PrintController@pagePrintDesign')->name('design');
});
