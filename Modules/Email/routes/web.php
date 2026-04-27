<?php

use Illuminate\Support\Facades\Route;
use Modules\Email\Http\Controllers\EmailAccountsController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/email/accounts', [EmailAccountsController::class, 'index'])->name('hosts.email.accounts');
    Route::post('/hosts/{hosting}/email/accounts', [EmailAccountsController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.accounts.store');
    Route::post('/hosts/{hosting}/email/roundcube/deploy', [EmailAccountsController::class, 'deployRoundcube'])
        ->middleware('throttle:10,1')
        ->name('hosts.email.roundcube.deploy');
    Route::delete('/hosts/{hosting}/email/accounts/{emailAccount}', [EmailAccountsController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.accounts.destroy');
    Route::get('/hosts/{hosting}/email/accounts/{emailAccount}/webmail-login', [EmailAccountsController::class, 'webmailLogin'])
        ->middleware('throttle:60,1')
        ->name('hosts.email.accounts.webmail-login');
});
