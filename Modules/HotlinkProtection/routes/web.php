<?php

use Illuminate\Support\Facades\Route;
use Modules\HotlinkProtection\Http\Controllers\HotlinkProtectionController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/hotlink-protection', [HotlinkProtectionController::class, 'index'])->name('hosts.hotlink-protection');
    Route::post('/hosts/{hosting}/hotlink-protection', [HotlinkProtectionController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('hosts.hotlink-protection.update');
});
