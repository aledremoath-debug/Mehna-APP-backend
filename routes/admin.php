<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MainCategoryController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AiAssistantController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    
    // Auth Routes
    Route::get('/login', function () {
        return view('admin.auth.login');
    })->name('login');
    
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected Routes
    Route::middleware(['auth', 'admin'])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Users Management
        Route::resource('users', UserController::class);
        Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Locations Management
        Route::resource('locations', LocationController::class);

        // Services & Categories Management
        Route::resource('categories', MainCategoryController::class);
        Route::resource('sub_categories', SubCategoryController::class);
        Route::resource('services', ServiceController::class);
        // Products Management
        Route::resource('products', ProductController::class);
        // Product Categories Management
        Route::resource('product_categories', \App\Http\Controllers\Admin\ProductCategoryController::class);
        // Orders & Complaints
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('complaints', [\App\Http\Controllers\Admin\ComplaintController::class, 'index'])->name('complaints.index');
        Route::post('complaints/{id}/status', [\App\Http\Controllers\Admin\ComplaintController::class, 'updateStatus'])->name('complaints.status');
        
        // Join Requests
        Route::get('join-requests', [OrderController::class, 'joinRequests'])->name('join-requests.index');
        Route::post('join-requests/{id}/approve', [OrderController::class, 'approve'])->name('join-requests.approve');
        Route::post('join-requests/{id}/reject', [OrderController::class, 'reject'])->name('join-requests.reject');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        // App Settings
        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
        // AI Assistant Management
        Route::get('ai', [AiAssistantController::class, 'index'])->name('ai.index');
        Route::get('ai/knowledge', [AiAssistantController::class, 'knowledgeIndex'])->name('ai.knowledge.index');
        Route::post('ai/knowledge', [AiAssistantController::class, 'knowledgeStore'])->name('ai.knowledge.store');
        Route::put('ai/knowledge/{id}', [AiAssistantController::class, 'knowledgeUpdate'])->name('ai.knowledge.update');
        Route::delete('ai/knowledge/{id}', [AiAssistantController::class, 'knowledgeDestroy'])->name('ai.knowledge.destroy');
        Route::post('ai/toggle-status', [AiAssistantController::class, 'toggleStatus'])->name('ai.toggle');

        Route::get('ai/sessions', [AiAssistantController::class, 'sessionsIndex'])->name('ai.sessions.index');
        Route::get('ai/sessions/{id}', [AiAssistantController::class, 'sessionShow'])->name('ai.sessions.show');
    });
});
