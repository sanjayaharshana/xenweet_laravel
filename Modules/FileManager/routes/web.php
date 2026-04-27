<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManager\Http\Controllers\FileManagerController;

Route::middleware(['host.access'])->group(function () {
    Route::get('/hosts/{hosting}/files', [FileManagerController::class, 'index'])->name('hosts.files.index');
    Route::post('/hosts/{hosting}/files/mkdir', [FileManagerController::class, 'mkdir'])->name('hosts.files.mkdir');
    Route::post('/hosts/{hosting}/files/touch', [FileManagerController::class, 'touch'])->name('hosts.files.touch');
    Route::post('/hosts/{hosting}/files/delete', [FileManagerController::class, 'destroy'])->name('hosts.files.destroy');
    Route::post('/hosts/{hosting}/files/move', [FileManagerController::class, 'move'])->name('hosts.files.move');
    Route::post('/hosts/{hosting}/files/upload', [FileManagerController::class, 'upload'])->name('hosts.files.upload');
    Route::post('/hosts/{hosting}/files/rename', [FileManagerController::class, 'rename'])->name('hosts.files.rename');
    Route::get('/hosts/{hosting}/files/open', [FileManagerController::class, 'openFile'])->name('hosts.files.open');
    Route::get('/hosts/{hosting}/files/edit', [FileManagerController::class, 'edit'])->name('hosts.files.edit');
    Route::post('/hosts/{hosting}/files/edit', [FileManagerController::class, 'update'])->name('hosts.files.update');
    Route::post('/hosts/{hosting}/files/duplicate', [FileManagerController::class, 'duplicate'])->name('hosts.files.duplicate');
    Route::post('/hosts/{hosting}/files/compress', [FileManagerController::class, 'compress'])->name('hosts.files.compress');
    Route::post('/hosts/{hosting}/files/extract', [FileManagerController::class, 'extract'])->name('hosts.files.extract');
    Route::get('/hosts/{hosting}/files/queue-status', [FileManagerController::class, 'queueStatus'])->name('hosts.files.queue-status');
    Route::get('/hosts/{hosting}/files/entries', [FileManagerController::class, 'entries'])->name('hosts.files.entries');
    Route::get('/hosts/{hosting}/files/code-editor', [FileManagerController::class, 'codeEditor'])->name('hosts.files.code-editor');
});
