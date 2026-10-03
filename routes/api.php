<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\ActivitySyncController;
use App\Http\Controllers\Api\DonationController;

Route::get('/health', fn () => response()->json([
    'ok' => true,
    'service' => 'bafoundation-api',
]));

Route::get('/works', [WorkController::class, 'index']);
Route::get('/works/pending', [WorkController::class, 'pending'])->middleware('auth:sanctum');
Route::get('/works/{work}', [WorkController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/members', [MemberController::class, 'index']);
Route::get('/donation-categories', [DonationController::class, 'categories']);

Route::post('/donations', [DonationController::class, 'store'])->middleware('throttle:10,1');
Route::match(['get', 'post'], '/donations/payment/success', [DonationController::class, 'success'])->middleware('throttle:30,1');
Route::match(['get', 'post'], '/donations/payment/fail', [DonationController::class, 'fail'])->middleware('throttle:30,1');
Route::match(['get', 'post'], '/donations/payment/cancel', [DonationController::class, 'cancel'])->middleware('throttle:30,1');
Route::post('/donations/payment/ipn', [DonationController::class, 'ipn'])->middleware('throttle:120,1');
Route::get('/donations/{tranId}', [DonationController::class, 'status'])->middleware(['auth:sanctum', 'throttle:60,1']);

Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,10');
Route::post('/tracking/event', [TrackingController::class, 'trackEvent'])->middleware('throttle:120,1');
Route::post('/tracking/pwa', [TrackingController::class, 'syncPwaStatus'])->middleware('throttle:30,1');
Route::post('/tracking/sync', [ActivitySyncController::class, 'sync'])->middleware('throttle:30,1');
Route::post('/works', [WorkController::class, 'store'])->middleware('throttle:10,1');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,10');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,10');

    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])->middleware(['web', 'throttle:10,1']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->middleware(['web', 'throttle:10,1']);
    Route::post('/google/exchange', [GoogleAuthController::class, 'exchange'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'update']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
    });

    Route::get('/donations', [DonationController::class, 'history']);
    Route::get('/my-works', [WorkController::class, 'myWorks']);
    Route::get('/works/{work}/view', [WorkController::class, 'view']);
    Route::post('/works/{work}/vote', [WorkController::class, 'vote']);
    Route::delete('/works/{work}/vote', [WorkController::class, 'undoVote']);

    Route::middleware('permission:biodata-manage')->prefix('admin')->group(function () {
        Route::get('/profiles/pending', [AdminProfileController::class, 'pending']);
        Route::post('/profiles/{profile}/approve', [AdminProfileController::class, 'approve']);
        Route::post('/profiles/{profile}/reject', [AdminProfileController::class, 'reject']);
    });

    Route::prefix('admin/contact')->group(function () {
        Route::get('/', [ContactController::class, 'index']);
        Route::get('/{contactMessage}', [ContactController::class, 'show']);
    });
});
