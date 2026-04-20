<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManager\Http\Controllers\FileManagerController;

Route::middleware(['auth'])->group(function () {
    Route::get('/hosts/{hosting}/files', [FileManagerController::class, 'index'])->name('hosts.files.index');
});
