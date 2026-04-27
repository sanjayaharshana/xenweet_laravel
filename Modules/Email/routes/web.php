<?php

use Illuminate\Support\Facades\Route;
use Modules\Email\Http\Controllers\EmailAccountsController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/email/accounts', [EmailAccountsController::class, 'index'])->name('hosts.email.accounts');
    Route::post('/hosts/{hosting}/email/accounts', [EmailAccountsController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.accounts.store');
    Route::delete('/hosts/{hosting}/email/accounts/{emailAccount}', [EmailAccountsController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.accounts.destroy');
});
