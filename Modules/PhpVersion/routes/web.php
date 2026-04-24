<?php

use Illuminate\Support\Facades\Route;
use Modules\PhpVersion\Http\Controllers\PhpVersionController;

Route::middleware(['auth'])->group(function () {
    Route::get('/hosts/{hosting}/php-version', [PhpVersionController::class, 'index'])->name('hosts.php-version');
    Route::post('/hosts/{hosting}/php-version', [PhpVersionController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('hosts.php-version.update');
});
