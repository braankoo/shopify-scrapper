<?php

use Illuminate\Http\Request;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::resource('site', \App\Http\Controllers\SiteController::class);

Route::get('/catalog', [ \App\Http\Controllers\CatalogController::class, 'index' ]);
Route::get('/product', [ \App\Http\Controllers\ProductController::class, 'index' ]);
Route::get('/product/data', [ \App\Http\Controllers\ProductController::class, 'data' ]);
Route::get('/{variant}/historical', [ \App\Http\Controllers\HistoricalController::class, 'index' ]);
