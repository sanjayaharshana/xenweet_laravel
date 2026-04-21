<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/panel/logs', LogViewerController::class)->name('panel.logs');
    Route::get('/panel/settings', [AdminSettingsController::class, 'index'])->name('panel.settings');
    Route::post('/panel/settings', [AdminSettingsController::class, 'update'])->name('panel.settings.update');
    Route::post('/panel/settings/test-db', [AdminSettingsController::class, 'testDb'])->name('panel.settings.test-db');
    Route::get('/hosts/create', [PanelController::class, 'create'])->name('hosts.create');
    Route::post('/hosts', [PanelController::class, 'store'])->name('hosts.store');
    Route::delete('/hosts/{hosting}', [PanelController::class, 'destroy'])->name('hosts.destroy');
    Route::get('/hosts/{hosting}/panel', [PanelController::class, 'hostPanel'])->name('hosts.panel');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
