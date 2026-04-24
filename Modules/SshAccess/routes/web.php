<?php

use Illuminate\Support\Facades\Route;
use Modules\SshAccess\Http\Controllers\SshAccessController;

Route::middleware(['auth'])->group(function () {
    Route::get('/hosts/{hosting}/ssh-access', [SshAccessController::class, 'index'])
        ->name('hosts.ssh-access');
    Route::get('/hosts/{hosting}/terminal', [SshAccessController::class, 'terminal'])
        ->name('hosts.terminal');
    Route::post('/hosts/{hosting}/terminal/run', [SshAccessController::class, 'runTerminalCommand'])
        ->middleware('throttle:60,1')
        ->name('hosts.terminal.run');
});
