<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PhotoAdminController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PhotoFeedController;
use App\Http\Controllers\PhotoUploadController;
use App\Http\Controllers\QrController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/qr', [QrController::class, 'show'])->name('qr');

Route::get('/api/photos', [PhotoFeedController::class, 'index'])->name('photos.index');
Route::get('/api/photos/since', [PhotoFeedController::class, 'since'])->name('photos.since');

// Had kadar melindungi majlis daripada seorang tetamu membanjiri galeri,
// tetapi cukup longgar untuk sesiapa yang memuat naik beberapa gambar sekaligus.
Route::post('/api/photos', [PhotoUploadController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('photos.store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('tetapan', [SettingController::class, 'edit'])->name('settings');
        Route::put('tetapan', [SettingController::class, 'update'])->name('settings.update');

        Route::post('arkib', [PhotoAdminController::class, 'requestArchive'])->name('archives.store');
        Route::get('arkib/{archive}/muat-turun', [PhotoAdminController::class, 'downloadArchive'])->name('archives.download');
        Route::delete('arkib/{archive}', [PhotoAdminController::class, 'destroyArchive'])->name('archives.destroy');
        Route::get('gambar/{photo}/muat-turun', [PhotoAdminController::class, 'download'])->name('photos.download');
        Route::patch('gambar/{photo}/papar', [PhotoAdminController::class, 'toggle'])->name('photos.toggle');
        Route::delete('gambar/{photo}', [PhotoAdminController::class, 'destroy'])->name('photos.destroy');
    });
});

/*
 | Cron web untuk hosting tanpa SSH. Dihadkan kadarnya kerana ia mencetuskan
 | kerja sebenar; token salah mengembalikan 404, jadi mengimbas untuk mencari
 | laluan ini tidak memberi apa-apa petunjuk.
 */
Route::get('cron/{token}', CronController::class)
    ->middleware('throttle:12,1')
    ->name('cron');
