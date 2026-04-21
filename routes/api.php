<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExternalApiController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TicketCommentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Tickets
    Route::apiResource('tickets', TicketController::class);
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
    Route::post('/tickets/{ticket}/auto-assign', [TicketController::class, 'autoAssign']);
    Route::post('/tickets/{ticket}/self-assign', [TicketController::class, 'selfAssign']);
    Route::put('/tickets/{ticket}/tags', [TicketController::class, 'updateTags']);

    // Comments
    Route::get('/tickets/{ticket}/comments', [TicketCommentController::class, 'index']);
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store']);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Agents
    Route::get('/agents', [UserController::class, 'agents']);

    // Tags
    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);

    // Notifications
    Route::get('/notifications', [UserController::class, 'notifications']);
    Route::post('/notifications/{id}/read', [UserController::class, 'markNotificationRead']);
    Route::post('/notifications/read-all', [UserController::class, 'markAllNotificationsRead']);

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // AI
    Route::post('/ai/summarize', [AIController::class, 'summarize']);
    Route::post('/ai/reply', [AIController::class, 'suggestReply']);
});

// External API routes (for integrated apps like ShopHub)
Route::prefix('external')->middleware('external.app')->group(function () {
    // Tickets
    Route::post('/tickets', [ExternalApiController::class, 'createTicket']);
    Route::get('/tickets', [ExternalApiController::class, 'getCustomerTickets']);
    Route::get('/tickets/{ticketId}', [ExternalApiController::class, 'getTicket']);
    Route::post('/tickets/{ticketId}/comments', [ExternalApiController::class, 'addComment']);

    // Categories
    Route::get('/categories', [ExternalApiController::class, 'getCategories']);

    // Webhook registration
    Route::post('/webhook', [ExternalApiController::class, 'registerWebhook']);
});
