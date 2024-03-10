<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ProfileController;

Route::prefix('v1')->group(function () {
    Route::post('/signup', [WalletController::class, 'signUp']);
    Route::post('/init', [WalletController::class, 'initialize']);
    Route::post('/wallet', [WalletController::class, 'enableWallet']);
    Route::get('/wallet', [WalletController::class, 'viewBalance']);
    Route::get('/wallet/transactions', [WalletController::class, 'viewTransactions']);
    Route::post('/wallet/deposits', [WalletController::class, 'addDeposit']);
    Route::post('/wallet/withdrawals', [WalletController::class, 'makeWithdrawal']);
    Route::patch('/wallet', [WalletController::class, 'disableWallet']);
});
