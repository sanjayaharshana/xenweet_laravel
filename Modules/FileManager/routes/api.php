<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManager\Http\Controllers\FileManagerController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('filemanagers', FileManagerController::class)->names('filemanager');
});
