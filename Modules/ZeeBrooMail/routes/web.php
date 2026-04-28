<?php

use Illuminate\Support\Facades\Route;
use Modules\ZeeBrooMail\Http\Controllers\ZeeBrooMailController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/zeebroo-mail', [ZeeBrooMailController::class, 'index'])
        ->name('hosts.zeebroo-mail.index');
    Route::get('/hosts/{hosting}/zeebroo-mail/data', [ZeeBrooMailController::class, 'mailboxData'])
        ->name('hosts.zeebroo-mail.data');
    Route::get('/hosts/{hosting}/zeebroo-mail/message/{uid}', [ZeeBrooMailController::class, 'show'])
        ->whereNumber('uid')
        ->name('hosts.zeebroo-mail.show');
    Route::get('/hosts/{hosting}/zeebroo-mail/message/{uid}/data', [ZeeBrooMailController::class, 'messageData'])
        ->whereNumber('uid')
        ->name('hosts.zeebroo-mail.message.data');
    Route::post('/hosts/{hosting}/zeebroo-mail/send', [ZeeBrooMailController::class, 'send'])
        ->middleware('throttle:30,1')
        ->name('hosts.zeebroo-mail.send');
    Route::post('/hosts/{hosting}/zeebroo-mail/send-ajax', [ZeeBrooMailController::class, 'sendAjax'])
        ->middleware('throttle:30,1')
        ->name('hosts.zeebroo-mail.send-ajax');
});
