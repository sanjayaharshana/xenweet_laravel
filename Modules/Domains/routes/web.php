<?php

use Illuminate\Support\Facades\Route;
use Modules\Domains\Http\Controllers\DomainsController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/domains', [DomainsController::class, 'index'])->name('hosts.domains.index');
    Route::post('/hosts/{hosting}/domains', [DomainsController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('hosts.domains.store');
    Route::delete('/hosts/{hosting}/domains/{hostDomain}', [DomainsController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('hosts.domains.destroy');
    Route::post('/hosts/{hosting}/domains/redirects', [DomainsController::class, 'storeRedirect'])
        ->middleware('throttle:30,1')
        ->name('hosts.domains.redirects.store');
    Route::delete('/hosts/{hosting}/domains/redirects/{redirect}', [DomainsController::class, 'destroyRedirect'])
        ->middleware('throttle:30,1')
        ->name('hosts.domains.redirects.destroy');
});
