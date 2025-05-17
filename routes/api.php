<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RequestOrderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;



// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Product listing (public)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Cart  (customer)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    
    // Order  (customer)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/payment-methods', [OrderController::class, 'getPaymentMethods']);
    
    // Notification (all authenticated users)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    
    // Dashboard (role-specific)
    Route::get('/dashboard/customer', [DashboardController::class, 'customerDashboard']);
    
    
    // Product management
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
    
    // Request orders
    Route::get('/request-orders', [RequestOrderController::class, 'index']);
    Route::post('/request-orders', [RequestOrderController::class, 'store']);
    Route::get('/request-orders/{id}', [RequestOrderController::class, 'show']);
    Route::put('/request-orders/{id}/admin-approval', [RequestOrderController::class, 'adminApproval']);
    
    // Admin dashboard
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);
    
    
    // Request orders approval
    Route::put('/request-orders/{id}/warehouse-approval', [RequestOrderController::class, 'warehouseApproval']);
    
    // Warehouse dashboard
    Route::get('/dashboard/warehouse', [DashboardController::class, 'warehouseDashboard']);
    
  
    // Order processing
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{id}/payment', [OrderController::class, 'updatePaymentStatus']);
    
    // Staff dashboard
    Route::get('/dashboard/staff', [DashboardController::class, 'staffDashboard']);
    
    // COMMENTED OUT ALL MIDDLEWARE GROUPS UNTIL THEY'RE PROPERLY SET UP
    /*
    // Admin routes
    Route::middleware('admin')->group(function () {
        // Product management
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
        
        // Request orders
        Route::get('/request-orders', [RequestOrderController::class, 'index']);
        Route::post('/request-orders', [RequestOrderController::class, 'store']);
        Route::get('/request-orders/{id}', [RequestOrderController::class, 'show']);
        Route::put('/request-orders/{id}/admin-approval', [RequestOrderController::class, 'adminApproval']);
        
        // Admin dashboard
        Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);
    });
    
    // Warehouse manager routes
    Route::middleware('warehouse')->group(function () {
        // Request orders approval
        Route::get('/request-orders', [RequestOrderController::class, 'index']);
        Route::get('/request-orders/{id}', [RequestOrderController::class, 'show']);
        Route::put('/request-orders/{id}/warehouse-approval', [RequestOrderController::class, 'warehouseApproval']);
        
        // Warehouse dashboard
        Route::get('/dashboard/warehouse', [DashboardController::class, 'warehouseDashboard']);
    });
    
    // Staff routes
    Route::middleware('staff')->group(function () {
        // Order processing
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment', [OrderController::class, 'updatePaymentStatus']);
        
        // Staff dashboard
        Route::get('/dashboard/staff', [DashboardController::class, 'staffDashboard']);
    });
    */
});