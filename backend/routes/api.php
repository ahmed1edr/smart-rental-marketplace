<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\NotificationController;

// 1. روابط المصادقة (تبدأ بـ /api/auth)
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// روابط العقارات والتقييمات العامة (بلا Token)
Route::get('properties', [PropertyController::class, 'index']);
Route::get('properties/{id}', [PropertyController::class, 'show'])->where('id', '[0-9]+');
Route::get('properties/{id}/booked-dates', [PropertyController::class, 'getBookedDates']);
Route::get('properties/{property_id}/reviews', [ReviewController::class, 'index']);

// 3. الروابط المحمية (ضروري Token باش تدخل ليها)
Route::group(['middleware' => 'auth:api'], function () {

    // مسارات المصادقة المحمية
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    // Messages
    Route::get('/messages/conversations', [MessageController::class, 'getConversations']);
    Route::get('/messages/{userId}', [MessageController::class, 'getMessages']);
    Route::post('/messages', [MessageController::class, 'sendMessage']);
    Route::patch('/messages/{userId}/read', [MessageController::class, 'markAsRead']);

    // العقارات CRUD
    Route::get('properties/mine', [PropertyController::class, 'myProperties']);
    Route::post('properties', [PropertyController::class, 'store']);
    Route::put('properties/{id}', [PropertyController::class, 'update']);
    Route::delete('properties/{id}', [PropertyController::class, 'destroy']);

    // الحجوزات Bookings
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('bookings/me', [BookingController::class, 'myBookings']);
    Route::patch('bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // تأكيد الدفع Transactions
    Route::post('bookings/{booking_id}/valider-paiement', [TransactionController::class, 'validerPaiement']);

    // تقييمات Reviews
    Route::post('properties/{property_id}/reviews', [ReviewController::class, 'store']);
    Route::post('reviews/{id}/report', [ReviewController::class, 'report']);

    // المفضلة Favorites
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites/{propertyId}', [FavoriteController::class, 'toggle']);

    // الاشعارات Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

// 4. روابط الأدمن (ضروري Token + دور admin)
Route::group(['middleware' => ['auth:api', 'admin'], 'prefix' => 'admin'], function () {
    Route::get('stats', [AdminController::class, 'stats']);
    Route::get('users', [AdminController::class, 'users']);
    Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
    Route::get('properties', [AdminController::class, 'properties']);
    Route::delete('properties/{id}', [AdminController::class, 'deleteProperty']);
    Route::get('bookings', [AdminController::class, 'bookings']);
    Route::get('reported-reviews', [AdminController::class, 'reportedReviews']);
    Route::delete('reviews/{id}', [AdminController::class, 'deleteReview']);
    Route::patch('reviews/{id}/dismiss', [AdminController::class, 'dismissReport']);
});

