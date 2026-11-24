<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\VoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->prefix('chat')->group(function () {
    Route::post('/send', [ChatController::class, 'sendMessage']);
    Route::post('/booking', [ChatController::class, 'submitBooking']);
    Route::post('/upload-pdf', [ChatController::class, 'uploadPdf']);
    Route::get('/history', [ChatController::class, 'getHistory']);
    Route::get('/conversations', [ChatController::class, 'getUserConversations']);
    Route::post('/new', [ChatController::class, 'newConversation']);
    Route::get('/rooms', [ChatController::class, 'getRooms']);
});

// Deepgram Voice AI Endpoints (TTS Flux/Aura-2 & STT/STS Nova-3)
Route::middleware(['web'])->prefix('voice')->group(function () {
    Route::get('/config', [VoiceController::class, 'config']);
    Route::post('/speak', [VoiceController::class, 'speak']);
    Route::post('/transcribe', [VoiceController::class, 'transcribe']);
    Route::post('/converse', [VoiceController::class, 'converse']);
});
