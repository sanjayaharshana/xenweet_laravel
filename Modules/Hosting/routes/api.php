<?php

use Illuminate\Support\Facades\Route;
use Modules\Hosting\Http\Controllers\HostingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('hostings', HostingController::class)->names('hosting');
});
