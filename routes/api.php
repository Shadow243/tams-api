<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Configs\BranchController;
use App\Http\Controllers\Api\Configs\CountryController;
use App\Http\Controllers\Api\Configs\FeeRuleController;
use App\Http\Controllers\Api\Configs\OperatorController;
use App\Http\Controllers\Api\Configs\TransactionTypeController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\Configs\WalletController;
use App\Http\Controllers\Api\Users\UserController;
use App\Http\Resources\Api\UserResource;

Route::get('/user', function (Request $request) {
    return new UserResource($request->user());
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
    
    // Currencies Routes
    Route::get('currencies', [CurrencyController::class, 'index'])->middleware('permission:lire_devises')->name('currencies.index');
    Route::get('currencies/all', [CurrencyController::class, 'all'])->name('currencies.all'); // For dropdowns (no pagination)
    Route::get('currencies/active', [CurrencyController::class, 'active'])->name('currencies.active'); // Active currencies for dropdowns
    Route::get('currencies/default', [CurrencyController::class, 'default'])->name('currencies.default');
    Route::post('currencies', [CurrencyController::class, 'store'])->middleware('permission:creer_devises')->name('currencies.store');
    Route::get('currencies/{code}', [CurrencyController::class, 'show'])->middleware('permission:lire_devises')->name('currencies.show');
    Route::put('currencies/{code}', [CurrencyController::class, 'update'])->middleware('permission:editer_devises')->name('currencies.update');
    Route::patch('currencies/{code}', [CurrencyController::class, 'update'])->middleware('permission:editer_devises');
    Route::delete('currencies/{code}', [CurrencyController::class, 'destroy'])->middleware('permission:supprimer_devises')->name('currencies.destroy');
    
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

        // Fee Rules Routes
        Route::get('fee-rules', [FeeRuleController::class, 'index'])->middleware('permission:lire_regles_frais')->name('fee-rules.index');
        Route::get('fee-rules/export/pdf', [FeeRuleController::class, 'exportPDF'])->middleware('permission:lire_regles_frais')->name('fee-rules.exportPDF');
        Route::get('fee-rules/applicable', [FeeRuleController::class, 'getApplicable'])->middleware('permission:lire_regles_frais')->name('fee-rules.applicable');
        Route::post('fee-rules', [FeeRuleController::class, 'store'])->middleware('permission:creer_regles_frais')->name('fee-rules.store');
        Route::get('fee-rules/{feeRule}', [FeeRuleController::class, 'show'])->middleware('permission:lire_regles_frais')->name('fee-rules.show');
        Route::put('fee-rules/{feeRule}', [FeeRuleController::class, 'update'])->middleware('permission:editer_regles_frais')->name('fee-rules.update');
        Route::patch('fee-rules/{feeRule}', [FeeRuleController::class, 'update'])->middleware('permission:editer_regles_frais');
        Route::delete('fee-rules/{feeRule}', [FeeRuleController::class, 'destroy'])->middleware('permission:supprimer_regles_frais')->name('fee-rules.destroy');
        Route::patch('fee-rules/{feeRule}/toggle-status', [FeeRuleController::class, 'toggleStatus'])->middleware('permission:editer_regles_frais')->name('fee-rules.toggle-status');
    });

    // Transactions Routes
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->middleware('permission:lire_transactions')->name('index');
        Route::get('/statistics', [TransactionController::class, 'statistics'])->middleware('permission:lire_transactions')->name('statistics');
        Route::get('/export/pdf', [TransactionController::class, 'exportPDF'])->middleware('permission:lire_transactions')->name('exportPDF');
        Route::post('/verify-withdrawal', [TransactionController::class, 'verifyWithdrawalCode'])->name('verify-withdrawal');
        Route::post('/', [TransactionController::class, 'store'])->middleware('permission:creer_transactions')->name('store');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->middleware('permission:lire_transactions')->name('show');
        Route::get('/{transaction}/receipt', [TransactionController::class, 'receipt'])->middleware('permission:lire_transactions')->name('receipt');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->middleware('permission:editer_transactions')->name('update');
        Route::patch('/{transaction}', [TransactionController::class, 'update'])->middleware('permission:editer_transactions');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->middleware('permission:supprimer_transactions')->name('destroy');
        Route::patch('/{transaction}/cancel', [TransactionController::class, 'cancel'])->middleware('permission:editer_transactions')->name('cancel');
        Route::patch('/{transaction}/complete', [TransactionController::class, 'complete'])->middleware('permission:editer_transactions')->name('complete');
        Route::patch('/{transaction}/change-status', [TransactionController::class, 'changeStatus'])->middleware('permission:editer_transactions')->name('change-status');
    });

    // Customers Routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->middleware('permission:lire_clients')->name('index');
        Route::get('/find-by-phone', [CustomerController::class, 'findByPhone'])->middleware('permission:lire_clients')->name('find-by-phone');
        Route::get('/top', [CustomerController::class, 'topCustomers'])->middleware('permission:lire_clients')->name('top');
        Route::post('/', [CustomerController::class, 'store'])->middleware('permission:creer_clients')->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->middleware('permission:lire_clients')->name('show');
        Route::get('/{customer}/statistics', [CustomerController::class, 'statistics'])->middleware('permission:lire_clients')->name('statistics');
        Route::put('/{customer}', [CustomerController::class, 'update'])->middleware('permission:editer_clients')->name('update');
        Route::patch('/{customer}', [CustomerController::class, 'update'])->middleware('permission:editer_clients');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:supprimer_clients')->name('destroy');
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
