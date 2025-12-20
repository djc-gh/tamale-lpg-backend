<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\StationManagerAssignmentController;
use App\Http\Controllers\StationManagerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public station routes
Route::prefix('stations')->group(function () {
    Route::get('/', [StationController::class, 'index']);
    Route::get('/{id}', [StationController::class, 'show']);
    Route::post('/nearby', [StationController::class, 'nearbyStations']);
    Route::get('/{stationId}/price-history', [StationController::class, 'priceHistory']);
});

// Protected routes
Route::middleware('auth.api:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Admin-only station management routes
    Route::middleware('admin')->prefix('stations')->group(function () {
        Route::post('/', [StationController::class, 'store']);
        Route::put('/{id}', [StationController::class, 'update']);
        Route::delete('/{id}', [StationController::class, 'destroy']);
    });

    // Station status toggle - admins can toggle any station, managers can only toggle their own
    Route::middleware('auth.api:sanctum')->prefix('stations')->group(function () {
        Route::patch('/{id}/status', [StationController::class, 'toggleStatus']);
    });

    // Station availability toggle - admins can update any station, managers can only update their own
    Route::middleware('auth.api:sanctum')->prefix('stations')->group(function () {
        Route::patch('/{id}/availability', [StationController::class, 'updateAvailability']);
    });

    // Admin-only manager assignment routes
    Route::middleware('admin')->prefix('stations')->group(function () {
        Route::post('/{stationId}/assign-manager', [StationManagerAssignmentController::class, 'assignManager']);
        Route::delete('/{stationId}/remove-manager', [StationManagerAssignmentController::class, 'removeManager']);
        Route::get('/{stationId}/manager', [StationManagerAssignmentController::class, 'getCurrentManager']);
        Route::get('/{stationId}/manager-history', [StationManagerAssignmentController::class, 'getManagerHistory']);
    });

    // Admin-only station manager CRUD routes
    Route::middleware('admin')->prefix('managers')->group(function () {
        Route::get('/', [StationManagerController::class, 'index']);
        Route::get('/active', [StationManagerController::class, 'active']);
        Route::post('/', [StationManagerController::class, 'store']);
        Route::get('/{id}', [StationManagerController::class, 'show']);
        Route::put('/{id}', [StationManagerController::class, 'update']);
        Route::delete('/{id}', [StationManagerController::class, 'destroy']);
    });

    // Admin-only analytics routes
    Route::middleware('admin')->prefix('analytics')->group(function () {
        Route::get('/overview', [AnalyticsController::class, 'overview']);
        Route::get('/daily', [AnalyticsController::class, 'dailyStats']);
        Route::get('/monthly', [AnalyticsController::class, 'monthlyStats']);
        Route::get('/top-pages', [AnalyticsController::class, 'topPages']);
        Route::get('/devices', [AnalyticsController::class, 'deviceDistribution']);
        Route::get('/browsers', [AnalyticsController::class, 'browserDistribution']);
        Route::get('/operating-systems', [AnalyticsController::class, 'osDistribution']);
        Route::get('/returning-vs-new', [AnalyticsController::class, 'returningVsNew']);
    });
});
