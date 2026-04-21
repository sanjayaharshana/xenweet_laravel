<?php

use Illuminate\Support\Facades\Route;
use Modules\ManageDb\Http\Controllers\ManageDbController;

Route::middleware(['auth'])->group(function () {
    Route::get('/hosts/{hosting}/db/manage', [ManageDbController::class, 'index'])->name('hosts.db.manage');
    Route::post('/hosts/{hosting}/db/manage/database', [ManageDbController::class, 'createDatabase'])->name('hosts.db.create-database');
    Route::post('/hosts/{hosting}/db/manage/user', [ManageDbController::class, 'createUser'])->name('hosts.db.create-user');
});
