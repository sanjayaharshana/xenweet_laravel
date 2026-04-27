<?php

use Illuminate\Support\Facades\Route;
use Modules\Email\Http\Controllers\EmailAccountsController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/email', [EmailAccountsController::class, 'index'])->name('hosts.email.index');
    Route::get('/hosts/{hosting}/email/accounts', [EmailAccountsController::class, 'index'])->name('hosts.email.accounts');
    Route::post('/hosts/{hosting}/email/accounts', [EmailAccountsController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.accounts.store');
    Route::delete('/hosts/{hosting}/email/accounts/{emailAccount}', [EmailAccountsController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.accounts.destroy');
    Route::post('/hosts/{hosting}/email/accounts/{emailAccount}/roundcube-login', [EmailAccountsController::class, 'roundcubeAutoLogin'])
        ->middleware('throttle:20,1')
        ->name('hosts.email.accounts.roundcube-login');

    Route::post('/hosts/{hosting}/email/forwarders', [EmailAccountsController::class, 'storeForwarder'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.forwarders.store');
    Route::delete('/hosts/{hosting}/email/forwarders/{forwarder}', [EmailAccountsController::class, 'destroyForwarder'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.forwarders.destroy');

    Route::post('/hosts/{hosting}/email/autoresponders', [EmailAccountsController::class, 'storeAutoresponder'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.autoresponders.store');
    Route::delete('/hosts/{hosting}/email/autoresponders/{autoresponder}', [EmailAccountsController::class, 'destroyAutoresponder'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.autoresponders.destroy');

    Route::post('/hosts/{hosting}/email/filters', [EmailAccountsController::class, 'storeFilter'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.filters.store');
    Route::delete('/hosts/{hosting}/email/filters/{filter}', [EmailAccountsController::class, 'destroyFilter'])
        ->middleware('throttle:30,1')
        ->name('hosts.email.filters.destroy');
});
