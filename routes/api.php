<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('chat')->group(function () {
    Route::post('/send', [ChatController::class, 'sendMessage']);
    Route::post('/booking', [ChatController::class, 'submitBooking']);
    Route::post('/upload-pdf', [ChatController::class, 'uploadPdf']);
    Route::get('/history', [ChatController::class, 'getHistory']);
    Route::post('/new', [ChatController::class, 'newConversation']);
});
