<?php

use Illuminate\Support\Facades\Route;
use Modules\ManageDb\Http\Controllers\ManageDbController;

Route::middleware(['auth'])->group(function () {
    Route::get('/hosts/{hosting}/db/manage', [ManageDbController::class, 'index'])->name('hosts.db.manage');
    Route::get('/hosts/{hosting}/db/manage/mysql', [ManageDbController::class, 'mysql'])->name('hosts.db.manage.mysql');
    Route::get('/hosts/{hosting}/db/manage/mysql/adminer-login', [ManageDbController::class, 'adminerLogin'])->name('hosts.db.adminer-login');
    Route::post('/hosts/{hosting}/db/manage/mysql/access', [ManageDbController::class, 'applyAccessGraph'])->name('hosts.db.apply-access-graph');
    Route::post('/hosts/{hosting}/db/manage/database', [ManageDbController::class, 'createDatabase'])->name('hosts.db.create-database');
    Route::post('/hosts/{hosting}/db/manage/user', [ManageDbController::class, 'createUser'])->name('hosts.db.create-user');
});
