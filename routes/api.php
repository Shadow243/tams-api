<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/healthcheck', function () {
    return [
        'status' => 'up',
        'services' => [
            'database' => 'up',
            'redis' => 'up',
        ],
    ];
});

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', LoginController::class);

    Route::post('logout', LogoutController::class)->middleware('auth:sanctum')->name('logout');
});


