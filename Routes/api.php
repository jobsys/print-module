<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$route_prefix = config('module.Print.route_prefix', 'manager');
$route_url_prefix = $route_prefix ? $route_prefix . '/' : '';
$route_name_prefix = $route_prefix ? $route_prefix . '.' : '';

Route::prefix("{$route_url_prefix}print")->name("api.{$route_name_prefix}print.")->group(function () {
    Route::post('/print-template', "PrintController@templateEdit")->name('template.edit');
    Route::post('/print-template/variable', "PrintController@templateVariableEdit")->name('template.variable.edit');
    Route::post('/print-template/design', "PrintController@templateDesignEdit")->name('template.design.edit');
    Route::get('/print-template', 'PrintController@templateItems')->name('template.items');
    Route::get('/print-template/{id}', 'PrintController@templateItem')->where('id', '[0-9]+')->name('template.item');
    Route::post('/print-template/delete', 'PrintController@templateDelete')->name('template.delete');
    Route::post('/print-template/copy', 'PrintController@templateCopy')->name('template.copy');

});
