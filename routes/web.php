<?php

use App\Http\Controllers\SwitchAdminLocaleController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Middleware\SetAdminLocale;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('admin-locale/{locale}', SwitchAdminLocaleController::class)
    ->middleware('auth')
    ->whereIn('locale', SetAdminLocale::SUPPORTED_LOCALES)
    ->name('admin.locale');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
    });

require __DIR__.'/settings.php';
