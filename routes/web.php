<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\PinChange;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('chat.index')
        : redirect()->route('login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', \App\Livewire\Auth\Register::class)->name('register');
});

// Authenticated routes (no "active" gate for pin change — locked users still need to update)
Route::middleware('auth')->group(function () {
    Route::get('/pin/change', PinChange::class)->name('pin.change');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Active authenticated routes — gated by EnsureActive (also forces pin change)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/chat', \App\Livewire\Chat\Index::class)->name('chat.index');
    Route::get('/settings', \App\Livewire\Profile\Settings::class)->name('settings');

    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])
        ->name('push.subscribe');
    Route::delete('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])
        ->name('push.unsubscribe');
});

// Admin
Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/users', \App\Livewire\Admin\Users::class)->name('users');
    Route::get('/deleted-messages', \App\Livewire\Admin\DeletedMessages::class)->name('deleted-messages');
    Route::get('/audit-logs', \App\Livewire\Admin\AuditLogs::class)->name('audit-logs');
    Route::get('/mail-test', \App\Livewire\Admin\MailTest::class)->name('mail-test');
});
