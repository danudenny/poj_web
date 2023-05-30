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

Route::group(['prefix' => 'v1'], function () {
    require __DIR__.'/auth.php';

    require __DIR__.'/admin.php';

    require __DIR__.'/mobile.php';


    Route::group(['prefix' => 'public'], function () {
        Route::get('tes', function () {
            return response()->json(['message' => 'tes endpoint API public']);
        });
    });
});
