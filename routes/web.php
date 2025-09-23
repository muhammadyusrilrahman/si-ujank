<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\SkpdController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/captcha', [AuthController::class, 'captcha'])->name('captcha');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    Route::middleware('role:super_admin')->group(function () {
        Route::resource('skpds', SkpdController::class)->except(['show']);
    });

    Route::middleware('role:super_admin,admin_unit,user_regular')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('pegawais', [PegawaiController::class, 'index'])->name('pegawais.index');
        Route::get('pegawais/export', [PegawaiController::class, 'export'])->name('pegawais.export');
    });

    Route::middleware('role:super_admin,admin_unit')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('pegawais/create', [PegawaiController::class, 'create'])->name('pegawais.create');
        Route::post('pegawais', [PegawaiController::class, 'store'])->name('pegawais.store');
        Route::get('pegawais/{pegawai}/edit', [PegawaiController::class, 'edit'])->name('pegawais.edit');
        Route::put('pegawais/{pegawai}', [PegawaiController::class, 'update'])->name('pegawais.update');
        Route::patch('pegawais/{pegawai}', [PegawaiController::class, 'update']);
        Route::delete('pegawais/{pegawai}', [PegawaiController::class, 'destroy'])->name('pegawais.destroy');
        Route::delete('pegawais/bulk-destroy', [PegawaiController::class, 'bulkDestroy'])->name('pegawais.bulk-destroy');

        Route::get('pegawais/template', [PegawaiController::class, 'template'])->name('pegawais.template');
        Route::post('pegawais/import', [PegawaiController::class, 'import'])->name('pegawais.import');
    });
});


