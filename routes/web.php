<?php

use App\Http\Controllers\EmployeeAuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Halaman awal diarahkan ke login internal.
Route::get('/', function () {
    return redirect()->route('employee.login');
})->name('home');

// Login Super Admin dan Kasir.
Route::get('/login', [
    EmployeeAuthController::class,
    'create',
])->name('login');

Route::post('/login', [
    EmployeeAuthController::class,
    'store',
])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::get('/masuk', [
    EmployeeAuthController::class,
    'create',
])->name('employee.login');

Route::post('/masuk', [
    EmployeeAuthController::class,
    'store',
])
    ->middleware('throttle:5,1')
    ->name('employee.login.store');

// Logout akun internal.
Route::post('/keluar', [
    EmployeeAuthController::class,
    'destroy',
])
    ->middleware('auth')
    ->name('employee.logout');

Route::post('/logout', [
    EmployeeAuthController::class,
    'destroy',
])
    ->middleware('auth')
    ->name('logout');

// Profil akun internal.
Route::middleware('auth')
    ->group(function () {
        Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    });

// Kasir POS.
Route::middleware(['auth', 'can:use-pos'])
    ->prefix('kasir')
    ->name('pos.')
    ->group(function () {
        Route::get('/', [
            PosController::class,
            'index',
        ])->name('index');

        Route::get('/histori', [
            PosController::class,
            'history',
        ])->name('history');

        Route::post('/transaksi', [
            PosController::class,
            'store',
        ])->name('transactions.store');

        Route::get('/transaksi/{transactionNumber}', [
            PosController::class,
            'receipt',
        ])->name('receipts.show');
    });

// Laporan untuk Super Admin.
    Route::middleware(['auth', 'can:manage-store'])
        ->prefix('admin')
        ->group(function () {
            Route::get('/laporan', [
                ReportController::class,
                'index',
            ])->name('reports.index');

            Route::get('/laporan/stok', [
                ReportController::class,
                'stock',
            ])->name('reports.stock');
        });