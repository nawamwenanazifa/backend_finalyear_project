<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES - No authentication required
|--------------------------------------------------------------------------
*/

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);

// Public Gallery
Route::get('/gallery', [GalleryController::class, 'index']);

// Public Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Public Products - With cache headers for better performance
Route::get('/products', [ProductController::class, 'index'])->middleware('cache.headers:public;max_age=300;etag');
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/collections/{category}', [ProductController::class, 'getCollection']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES - Require authentication (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ==================== USER PROFILE ROUTES ====================
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/upload-image', [AuthController::class, 'uploadImage']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ==================== BOOKINGS ROUTES ====================
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // ==================== MESSAGES / CHAT ROUTES ====================
    Route::prefix('messages')->group(function () {

        // ---------- Conversations ----------
        // Get all conversations for logged-in user
        Route::get('/conversations', [MessageController::class, 'getConversations']);

        // Get or create a conversation with a specific user
        Route::post('/conversations', [MessageController::class, 'getOrCreateConversation']);

        // Get messages inside a conversation (used by Flutter chat screen)
        Route::get('/conversations/{conversationId}', [MessageController::class, 'getConversationMessages']);

        // Mark all messages in a conversation as read
        Route::post('/conversations/{conversationId}/read', [MessageController::class, 'markConversationAsRead']);

        // ---------- Sending Messages ----------
        // Send a text message (real-time via Reverb)
        Route::post('/send', [MessageController::class, 'sendMessage']);

        // Send an image (multipart/form-data)
        Route::post('/send-image', [MessageController::class, 'sendImage']);

        // Send audio (multipart/form-data)
        Route::post('/send-audio', [MessageController::class, 'sendAudio']);

        // Send image from Flutter Web (base64)
        Route::post('/send-image-web', [MessageController::class, 'sendImageWeb']);

        // Send audio from Flutter Web (base64)
        Route::post('/send-audio-web', [MessageController::class, 'sendAudioWeb']);

        // ---------- Legacy / Direct User Routes ----------
        // Get messages between auth user and another user (kept for backward compat)
        Route::get('/{userId}', [MessageController::class, 'getMessages']);

        // ---------- Read Receipts ----------
        Route::put('/{id}/read', [MessageController::class, 'markAsRead']);
        Route::get('/unread-count', [MessageController::class, 'getUnreadCount']);

        // ---------- Typing Indicators ----------
        Route::post('/typing', [MessageController::class, 'sendTypingStatus']);
        Route::get('/typing/{userId}', [MessageController::class, 'getTypingStatus']);

        // ---------- Delete ----------
        Route::delete('/{id}', [MessageController::class, 'deleteMessage']);
    });

    // ==================== ADMIN ONLY ROUTES ====================
    // Products Management
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Categories Management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Gallery Management
    Route::post('/gallery', [GalleryController::class, 'store']);
    Route::delete('/gallery/{id}', [GalleryController::class, 'destroy']);

    // Users Management (Admin only)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| HEALTH CHECK ROUTE (For testing)
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'message'   => 'API is running',
        'timestamp' => now(),
        'performance' => [
            'opcache_enabled' => function_exists('opcache_get_status'),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
        ]
    ]);
});

/*
|--------------------------------------------------------------------------
| DIAGNOSTIC ROUTE (For debugging - remove in production)
|--------------------------------------------------------------------------
*/

Route::get('/diagnostic', function () {
    return response()->json([
        'success' => true,
        'environment' => app()->environment(),
        'debug' => config('app.debug'),
        'timezone' => config('app.timezone'),
        'database' => [
            'connection' => config('database.default'),
            'connected' => true,
        ],
        'cache' => [
            'driver' => config('cache.default'),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
        ],
    ]);
});