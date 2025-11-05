<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DigitalBookController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\VideoTutorialController;
use App\Http\Controllers\TppController;
use App\Http\Controllers\TppCalculationController;
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
    Route::get('/login-activities', [AuthController::class, 'loginActivities'])->name('login-activities.index');

    Route::middleware('role:admin_unit')->group(function () {
        Route::post('feedbacks', [FeedbackController::class, 'store'])->name('feedbacks.store');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::resource('skpds', SkpdController::class)->except(['show']);
        Route::resource('digital-books', \App\Http\Controllers\DigitalBookController::class)->except(['show']);
        Route::resource('video-tutorials', \App\Http\Controllers\VideoTutorialController::class)->except(['show']);
        Route::post('feedbacks/{feedback}/reply', [FeedbackController::class, 'reply'])->name('feedbacks.reply')->whereNumber('feedback');
        Route::patch('feedbacks/{feedback}/toggle', [FeedbackController::class, 'toggle'])->name('feedbacks.toggle')->whereNumber('feedback');
    });

    Route::middleware('role:super_admin,admin_unit,user_regular')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('pegawais', [PegawaiController::class, 'index'])->name('pegawais.index');
        Route::get('pegawais/export', [PegawaiController::class, 'export'])->name('pegawais.export');
        Route::get('gajis', [GajiController::class, 'index'])->name('gajis.index');
        Route::get('gajis/export', [GajiController::class, 'export'])->name('gajis.export');        Route::get('tpps', [TppController::class, 'index'])->name('tpps.index');
        Route::get('tpps/perhitungan', [TppCalculationController::class, 'index'])->name('tpps.perhitungan');
        Route::get('tpps/perhitungan/export', [TppCalculationController::class, 'export'])->name('tpps.perhitungan.export');
        Route::get('tpps/perhitungan/template', [TppCalculationController::class, 'template'])->name('tpps.perhitungan.template');
        Route::get('tpps/export', [TppController::class, 'export'])->name('tpps.export');
    });

    Route::middleware('role:super_admin,admin_unit')->group(function () {
        Route::get('gajis/template', [GajiController::class, 'template'])->name('gajis.template');
        Route::post('gajis/import', [GajiController::class, 'import'])->name('gajis.import');
        Route::get('gajis/ebupot', [GajiController::class, 'indexEbupot'])->name('gajis.ebupot.index');
        Route::get('gajis/ebupot/{report}/download', [GajiController::class, 'downloadEbupot'])->name('gajis.ebupot.download')->whereNumber('report');
        Route::get('gajis/ebupot/create', [GajiController::class, 'createEbupot'])->name('gajis.ebupot.create');
        Route::post('gajis/ebupot', [GajiController::class, 'storeEbupot'])->name('gajis.ebupot.store');
        Route::get('gajis/create', [GajiController::class, 'create'])->name('gajis.create');
        Route::post('gajis', [GajiController::class, 'store'])->name('gajis.store');
        Route::get('gajis/{gaji}/edit', [GajiController::class, 'edit'])->name('gajis.edit');
        Route::put('gajis/{gaji}', [GajiController::class, 'update'])->name('gajis.update');
        Route::patch('gajis/{gaji}', [GajiController::class, 'update']);
        Route::delete('gajis/bulk-destroy', [GajiController::class, 'bulkDestroy'])->name('gajis.bulk-destroy');
        Route::delete('gajis/{gaji}', [GajiController::class, 'destroy'])->name('gajis.destroy')->whereNumber('gaji');

        Route::get('tpps/ebupot', [TppController::class, 'indexEbupot'])->name('tpps.ebupot.index');
        Route::get('tpps/ebupot/create', [TppController::class, 'createEbupot'])->name('tpps.ebupot.create');
        Route::post('tpps/ebupot', [TppController::class, 'storeEbupot'])->name('tpps.ebupot.store');
        Route::get('tpps/ebupot/{report}/download', [TppController::class, 'downloadEbupot'])->name('tpps.ebupot.download')->whereNumber('report');
        Route::get('tpps/template', [TppController::class, 'template'])->name('tpps.template');
        Route::post('tpps/import', [TppController::class, 'import'])->name('tpps.import');
        Route::get('tpps/create', [TppController::class, 'create'])->name('tpps.create');
        Route::post('tpps', [TppController::class, 'store'])->name('tpps.store');
        Route::get('tpps/perhitungan/create', [TppCalculationController::class, 'create'])->name('tpps.perhitungan.create');
        Route::post('tpps/perhitungan', [TppCalculationController::class, 'store'])->name('tpps.perhitungan.store');
        Route::get('tpps/perhitungan/{calculation}/edit', [TppCalculationController::class, 'edit'])->name('tpps.perhitungan.edit')->whereNumber('calculation');
        Route::put('tpps/perhitungan/{calculation}', [TppCalculationController::class, 'update'])->name('tpps.perhitungan.update')->whereNumber('calculation');
        Route::delete('tpps/perhitungan/{calculation}', [TppCalculationController::class, 'destroy'])->name('tpps.perhitungan.destroy')->whereNumber('calculation');
        Route::post('tpps/perhitungan/copy', [TppCalculationController::class, 'copy'])->name('tpps.perhitungan.copy');
        Route::post('tpps/perhitungan/import', [TppCalculationController::class, 'import'])->name('tpps.perhitungan.import');
        Route::get('tpps/{tpp}/edit', [TppController::class, 'edit'])->name('tpps.edit');
        Route::put('tpps/{tpp}', [TppController::class, 'update'])->name('tpps.update');
        Route::patch('tpps/{tpp}', [TppController::class, 'update']);
        Route::delete('tpps/bulk-destroy', [TppController::class, 'bulkDestroy'])->name('tpps.bulk-destroy');
        Route::delete('tpps/{tpp}', [TppController::class, 'destroy'])->name('tpps.destroy')->whereNumber('tpp');

        Route::get('skpds/profile', [SkpdController::class, 'profile'])->name('skpds.profile');
        Route::put('skpds/profile', [SkpdController::class, 'updateProfile'])->name('skpds.profile.update');
        Route::patch('skpds/profile', [SkpdController::class, 'updateProfile']);

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
        Route::delete('pegawais/bulk-destroy', [PegawaiController::class, 'bulkDestroy'])->name('pegawais.bulk-destroy');
        Route::delete('pegawais/{pegawai}', [PegawaiController::class, 'destroy'])->name('pegawais.destroy')->whereNumber('pegawai');
        Route::get('pegawais/template', [PegawaiController::class, 'template'])->name('pegawais.template');
        Route::post('pegawais/import', [PegawaiController::class, 'import'])->name('pegawais.import');
    });
});


