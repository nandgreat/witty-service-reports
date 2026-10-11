<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Coordinator;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/home', fn () => redirect(AuthController::home()))->middleware('auth')->name('home');

// ---------------------------------------------------------------- Admin interface
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\ReportController::class, 'dashboard'])->name('dashboard');

    Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');
    Route::post('reports/{report}/review', [Admin\ReportController::class, 'review'])->name('reports.review');
    Route::get('reports/{report}/download', [Admin\ReportController::class, 'download'])->name('reports.download');
    Route::delete('reports/{report}', [Admin\ReportController::class, 'destroy'])->name('reports.destroy');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::put('users/{user}/password', [Admin\UserController::class, 'password'])->name('users.password');
    Route::patch('users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');
    Route::delete('users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::prefix('catalog/{catalog}')->whereIn('catalog', ['service-types', 'tasks', 'platforms', 'workers'])->group(function () {
        Route::get('/', [Admin\CatalogController::class, 'index'])->name('catalog.index');
        Route::post('/', [Admin\CatalogController::class, 'store'])->name('catalog.store');
        Route::put('{id}', [Admin\CatalogController::class, 'update'])->name('catalog.update');
        Route::patch('{id}/toggle', [Admin\CatalogController::class, 'toggle'])->name('catalog.toggle');
        Route::delete('{id}', [Admin\CatalogController::class, 'destroy'])->name('catalog.destroy');
    });
});

// ------------------------------------------------------- Service coordinator interface
Route::middleware(['auth', 'role:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::get('service-types/{serviceType}/tasks', [Coordinator\TemplateController::class, 'tasks'])->name('tasks');

    Route::get('reports', [Coordinator\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/create', [Coordinator\ReportController::class, 'create'])->name('reports.create');
    Route::post('reports', [Coordinator\ReportController::class, 'store'])->name('reports.store');
    Route::get('reports/{report}', [Coordinator\ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{report}/edit', [Coordinator\ReportController::class, 'edit'])->name('reports.edit');
    Route::put('reports/{report}', [Coordinator\ReportController::class, 'update'])->name('reports.update');
    Route::delete('reports/{report}', [Coordinator\ReportController::class, 'destroy'])->name('reports.destroy');
    Route::post('reports/{report}/submit', [Coordinator\ReportController::class, 'submit'])->name('reports.submit');
    Route::post('reports/{report}/duplicate', [Coordinator\ReportController::class, 'duplicate'])->name('reports.duplicate');
    Route::get('reports/{report}/download', [Coordinator\ReportController::class, 'download'])->name('reports.download');
});
