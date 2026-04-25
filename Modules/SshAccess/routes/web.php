<?php

use Illuminate\Support\Facades\Route;
use Modules\SshAccess\Http\Controllers\SshAccessController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/ssh-access', [SshAccessController::class, 'index'])
        ->name('hosts.ssh-access');
    Route::post('/hosts/{hosting}/ssh-access/create-account', [SshAccessController::class, 'createJailedAccount'])
        ->middleware('throttle:15,1')
        ->name('hosts.ssh-access.create-account');
    Route::get('/hosts/{hosting}/terminal', [SshAccessController::class, 'terminal'])
        ->name('hosts.terminal');
    Route::post('/hosts/{hosting}/terminal/run', [SshAccessController::class, 'runTerminalCommand'])
        ->middleware('throttle:60,1')
        ->name('hosts.terminal.run');
});
