<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManager\Http\Controllers\FileManagerController;

Route::middleware(['auth'])->group(function () {
    Route::get('/hosts/{hosting}/files', [FileManagerController::class, 'index'])->name('hosts.files.index');
    Route::post('/hosts/{hosting}/files/mkdir', [FileManagerController::class, 'mkdir'])->name('hosts.files.mkdir');
    Route::post('/hosts/{hosting}/files/touch', [FileManagerController::class, 'touch'])->name('hosts.files.touch');
    Route::post('/hosts/{hosting}/files/delete', [FileManagerController::class, 'destroy'])->name('hosts.files.destroy');
    Route::post('/hosts/{hosting}/files/move', [FileManagerController::class, 'move'])->name('hosts.files.move');
    Route::post('/hosts/{hosting}/files/upload', [FileManagerController::class, 'upload'])->name('hosts.files.upload');
    Route::post('/hosts/{hosting}/files/rename', [FileManagerController::class, 'rename'])->name('hosts.files.rename');
});
