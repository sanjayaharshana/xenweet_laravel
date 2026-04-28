<?php

use Illuminate\Support\Facades\Route;
use Modules\WebMail\Http\Controllers\WebMailController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/webmail', [WebMailController::class, 'index'])
        ->name('hosts.webmail.index');
    Route::get('/hosts/{hosting}/webmail/message/{uid}', [WebMailController::class, 'show'])
        ->whereNumber('uid')
        ->name('hosts.webmail.show');
    Route::post('/hosts/{hosting}/webmail/send', [WebMailController::class, 'send'])
        ->middleware('throttle:30,1')
        ->name('hosts.webmail.send');
});
