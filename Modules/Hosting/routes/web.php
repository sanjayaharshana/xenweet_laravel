<?php

use Illuminate\Support\Facades\Route;
use Modules\Hosting\Http\Controllers\HostingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('hostings', HostingController::class)->names('hosting');
});
