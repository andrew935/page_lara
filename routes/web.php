<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TelegramConnectionController;
use App\Http\Controllers\TelegramLogController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\DomainSettingsController;
use App\Http\Controllers\DomainCheckSettingsController;
use App\Http\Controllers\AccountController;

/******** Auth ********/
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/******** Dashboards ********/
Route::get('/', function () {
    return redirect()->route('domains.index'); // redirect '/' to '/domains'
});
// Velzon "index" dashboard route -> redirect to domains (default landing page)
Route::get('index', fn () => redirect()->route('domains.index'))->name('index');

/******** User Management ********/
Route::middleware('auth')->group(function () {
    // Account / profile
    Route::get('account', [AccountController::class, 'show'])->name('account.show');

    Route::resource('users', UserController::class);

    // Roles & Permissions: Admin only
    Route::middleware('role:Admin')->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
    });

    // Connections
    Route::get('connections/telegram', [TelegramConnectionController::class, 'edit'])->name('connections.telegram.edit');
    Route::post('connections/telegram', [TelegramConnectionController::class, 'update'])->name('connections.telegram.update');
    Route::post('connections/telegram/test', [TelegramConnectionController::class, 'test'])->name('connections.telegram.test');
    Route::get('connections/telegram/logs', [TelegramLogController::class, 'index'])->name('connections.telegram.logs');

    // Domains
    Route::get('domains', [DomainController::class, 'index'])->name('domains.index');
    Route::post('domains', [DomainController::class, 'store'])->name('domains.store');
    Route::post('domains/ingest', [DomainController::class, 'ingest'])->name('domains.ingest');
    Route::post('domains/check-all', [DomainController::class, 'checkAll'])->name('domains.checkAll');
    Route::post('domains/{domain}/check', [DomainController::class, 'check'])->name('domains.check');
    Route::post('domains/{domain}/check-now', [DomainController::class, 'checkNow'])->name('domains.checkNow');
    Route::put('domains/{domain}', [DomainController::class, 'update'])->name('domains.update');
    Route::delete('domains/{domain}', [DomainController::class, 'destroy'])->name('domains.destroy');
    Route::post('domains/delete-all', [DomainController::class, 'deleteAll'])->name('domains.deleteAll');
    Route::post('domains/import-json', [DomainController::class, 'importJson'])->name('domains.importJson');
    Route::post('domains/import-latest', [DomainController::class, 'importLatest'])->name('domains.importLatest');

    // Domain settings
    Route::get('domains/settings', [DomainSettingsController::class, 'edit'])->name('domains.settings.edit');
    Route::post('domains/settings', [DomainSettingsController::class, 'update'])->name('domains.settings.update');

    // Domain check settings (toggle between server/cloudflare)
    Route::get('settings/domain-check', [DomainCheckSettingsController::class, 'index'])->name('settings.domain-check.index');
    Route::post('settings/domain-check', [DomainCheckSettingsController::class, 'update'])->name('settings.domain-check.update');
});