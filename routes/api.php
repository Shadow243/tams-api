<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Configs\BranchController;
use App\Http\Controllers\Api\Configs\CountryController;
use App\Http\Controllers\Api\Configs\OperatorController;
use App\Http\Controllers\Api\Configs\TransactionTypeController;
use App\Http\Controllers\Api\Configs\WalletController;
use App\Http\Controllers\Api\Users\UserController;

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


Route::get('locales', LocaleController::class)
    ->name('locales');

Route::get('translations/{locale}', [LocaleController::class, 'getTranslations'])
    ->where('locale', '[a-zA-Z_\-]+')
    ->name('translations.messages');


Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', LoginController::class);

    Route::post('logout', LogoutController::class)->middleware('auth:sanctum')->name('logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('me', [LogoutController::class, 'me'])->name('me');
    Route::name('configs.')->group(function () {
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

        // Branches Routes
        Route::get('branches', [BranchController::class, 'index'])->middleware('permission:lire_branches')->name('branches.index');
        Route::get('branches/export/pdf', [BranchController::class, 'exportPDF'])->middleware('permission:lire_branches')->name('branches.exportPDF');
        Route::post('branches', [BranchController::class, 'store'])->middleware('permission:creer_branches')->name('branches.store');
        Route::get('branches/{branch}', [BranchController::class, 'show'])->middleware('permission:lire_branches')->name('branches.show');
        Route::put('branches/{branch}', [BranchController::class, 'update'])->middleware('permission:editer_branches')->name('branches.update');
        Route::patch('branches/{branch}', [BranchController::class, 'update'])->middleware('permission:editer_branches');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('permission:supprimer_branches')->name('branches.destroy');
        Route::patch('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->middleware('permission:editer_branches')->name('branches.toggle-status');

        // Wallets Routes
        Route::get('wallets', [WalletController::class, 'index'])->middleware('permission:lire_portefeuilles')->name('wallets.index');
        Route::get('wallets/export/pdf', [WalletController::class, 'exportPDF'])->middleware('permission:lire_portefeuilles')->name('wallets.exportPDF');
        Route::post('wallets', [WalletController::class, 'store'])->middleware('permission:creer_portefeuilles')->name('wallets.store');
        Route::get('wallets/{wallet}', [WalletController::class, 'show'])->middleware('permission:lire_portefeuilles')->name('wallets.show');
        Route::put('wallets/{wallet}', [WalletController::class, 'update'])->middleware('permission:editer_portefeuilles')->name('wallets.update');
        Route::patch('wallets/{wallet}', [WalletController::class, 'update'])->middleware('permission:editer_portefeuilles');
        Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->middleware('permission:supprimer_portefeuilles')->name('wallets.destroy');
        Route::patch('wallets/{wallet}/toggle-status', [WalletController::class, 'toggleStatus'])->middleware('permission:editer_portefeuilles')->name('wallets.toggle-status');

        // Transaction Types Routes
        Route::get('transaction-types', [TransactionTypeController::class, 'index'])->middleware('permission:lire_types_operations')->name('transaction-types.index');
        Route::get('transaction-types/export/pdf', [TransactionTypeController::class, 'exportPDF'])->middleware('permission:lire_types_operations')->name('transaction-types.exportPDF');
        Route::post('transaction-types', [TransactionTypeController::class, 'store'])->middleware('permission:creer_types_operations')->name('transaction-types.store');
        Route::get('transaction-types/{transactionType}', [TransactionTypeController::class, 'show'])->middleware('permission:lire_types_operations')->name('transaction-types.show');
        Route::put('transaction-types/{transactionType}', [TransactionTypeController::class, 'update'])->middleware('permission:editer_types_operations')->name('transaction-types.update');
        Route::patch('transaction-types/{transactionType}', [TransactionTypeController::class, 'update'])->middleware('permission:editer_types_operations');
        Route::delete('transaction-types/{transactionType}', [TransactionTypeController::class, 'destroy'])->middleware('permission:supprimer_types_operations')->name('transaction-types.destroy');
    });

    Route::prefix('users')->name('users.')->group(function () {
        // Users Routes
        Route::get('/', [UserController::class, 'index'])->middleware('permission:lire_utilisateurs')->name('index');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:creer_utilisateurs')->name('store');
        Route::post('/bulk-delete', [UserController::class, 'bulkDestroy'])->middleware('permission:supprimer_utilisateurs')->name('bulkDestroy');
        Route::get('/export/pdf', [UserController::class, 'exportPDF'])->middleware('permission:lire_utilisateurs')->name('exportPDF');
        Route::get('{user}', [UserController::class, 'show'])->middleware('permission:lire_utilisateurs')->name('show');
        Route::put('{user}', [UserController::class, 'update'])->middleware('permission:editer_utilisateurs')->name('update');
        Route::patch('{user}', [UserController::class, 'update'])->middleware('permission:editer_utilisateurs');
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('permission:supprimer_utilisateurs')->name('destroy');
    });
});
