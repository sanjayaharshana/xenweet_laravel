<?php

use Illuminate\Support\Facades\Route;
use Modules\ZeeBrooMail\Http\Controllers\ZeeBrooMailController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/zeebroo-mail', [ZeeBrooMailController::class, 'index'])
        ->name('hosts.zeebroo-mail.index');
    Route::get('/hosts/{hosting}/zeebroo-mail/message/{uid}', [ZeeBrooMailController::class, 'show'])
        ->whereNumber('uid')
        ->name('hosts.zeebroo-mail.show');
    Route::post('/hosts/{hosting}/zeebroo-mail/send', [ZeeBrooMailController::class, 'send'])
        ->middleware('throttle:30,1')
        ->name('hosts.zeebroo-mail.send');
});
