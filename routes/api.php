<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Configs\CountryController;
use App\Http\Controllers\Api\Configs\OperatorController;

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

Route::name('configs.')->middleware('auth:sanctum')->group(function () {
    // Countries Routes
    Route::get('countries', [CountryController::class, 'index'])->middleware('permission:lire_pays')->name('countries.index');
    Route::post('countries', [CountryController::class, 'store'])->middleware('permission:creer_pays')->name('countries.store');
    Route::get('countries/{country}', [CountryController::class, 'show'])->middleware('permission:lire_pays')->name('countries.show');
    Route::put('countries/{country}', [CountryController::class, 'update'])->middleware('permission:editer_pays')->name('countries.update');
    Route::patch('countries/{country}', [CountryController::class, 'update'])->middleware('permission:editer_pays');
    Route::delete('countries/{country}', [CountryController::class, 'destroy'])->middleware('permission:supprimer_pays')->name('countries.destroy');

    // Operators Routes
    Route::get('operators', [OperatorController::class, 'index'])->middleware('permission:lire_operateurs')->name('operators.index');
    Route::post('operators', [OperatorController::class, 'store'])->middleware('permission:creer_operateurs')->name('operators.store');
    Route::get('operators/{operator}', [OperatorController::class, 'show'])->middleware('permission:lire_operateurs')->name('operators.show');
    Route::put('operators/{operator}', [OperatorController::class, 'update'])->middleware('permission:editer_operateurs')->name('operators.update');
    Route::patch('operators/{operator}', [OperatorController::class, 'update'])->middleware('permission:editer_operateurs');
    Route::delete('operators/{operator}', [OperatorController::class, 'destroy'])->middleware('permission:supprimer_operateurs')->name('operators.destroy');
});
