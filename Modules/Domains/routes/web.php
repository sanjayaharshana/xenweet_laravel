<?php

use Illuminate\Support\Facades\Route;
use Modules\Domains\Http\Controllers\DomainsController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/domains', [DomainsController::class, 'index'])->name('hosts.domains.index');
});
