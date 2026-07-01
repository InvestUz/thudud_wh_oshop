<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// --- Ochiq (public) sahifalar -------------------------------------------------
Route::get('/', [PublicController::class, 'landing'])->name('landing');
Route::post('/ariza-topshirish', [PublicController::class, 'submit'])->name('public.applications.submit');

// Kaskad tanlovlar uchun hudud JSON (ochiq)
Route::get('/geo/regions/{region}/districts', [GeoController::class, 'districts'])->name('geo.districts');
Route::get('/geo/districts/{district}/mahallas', [GeoController::class, 'mahallas'])->name('geo.mahallas');
Route::get('/geo/districts/{district}/streets', [GeoController::class, 'streets'])->name('geo.streets');

// --- Autentifikatsiya ---------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- Kabinet (autentifikatsiya talab qilinadi) --------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Arizalar (pipeline)
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/create', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/shartnoma-loyihasi', [ApplicationController::class, 'contractDraft'])->name('applications.contract-draft');
    Route::post('/applications/{application}/transition', [ApplicationController::class, 'transition'])->name('applications.transition');
    Route::post('/applications/{application}/survey', [ApplicationController::class, 'storeSurvey'])->name('applications.survey');
    Route::post('/applications/{application}/review', [ApplicationController::class, 'review'])->name('applications.review');

    // Shartnomalar
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/action', [ContractController::class, 'action'])->name('contracts.action');

    // Monitoring / hisobot
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
});
