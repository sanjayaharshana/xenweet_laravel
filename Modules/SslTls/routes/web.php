<?php

use Illuminate\Support\Facades\Route;
use Modules\SslTls\Http\Controllers\SslTlsController;

Route::middleware(['auth'])->group(function () {
    Route::get('/hosts/{hosting}/ssl-tls', [SslTlsController::class, 'index'])->name('hosts.ssl-tls');
    Route::post('/hosts/{hosting}/ssl-tls/generate-key', [SslTlsController::class, 'generatePrivateKey'])
        ->middleware('throttle:20,1')
        ->name('hosts.ssl-tls.generate-key');
    Route::post('/hosts/{hosting}/ssl-tls/generate-csr', [SslTlsController::class, 'generateCsr'])
        ->middleware('throttle:20,1')
        ->name('hosts.ssl-tls.generate-csr');
    Route::get('/hosts/{hosting}/ssl-tls/download-csr', [SslTlsController::class, 'downloadCsr'])
        ->middleware('throttle:60,1')
        ->name('hosts.ssl-tls.download-csr');
    Route::post('/hosts/{hosting}/ssl-tls/san-hostnames', [SslTlsController::class, 'updateSanHostnames'])
        ->middleware('throttle:30,1')
        ->name('hosts.ssl-tls.san-hostnames');
    Route::post('/hosts/{hosting}/ssl-tls/certificate', [SslTlsController::class, 'saveCertificate'])
        ->middleware('throttle:30,1')
        ->name('hosts.ssl-tls.certificate');
    Route::post('/hosts/{hosting}/ssl-tls/certificate/install', [SslTlsController::class, 'installCertificate'])
        ->middleware('throttle:20,1')
        ->name('hosts.ssl-tls.certificate.install');
});
