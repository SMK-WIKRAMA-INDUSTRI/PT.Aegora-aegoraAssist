<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// 1. Luxury Landing Page (Hotel Showcase & Investor Ready)
Route::get('/', [LandingController::class, 'index'])->name('landing');

// 2. Authentication (User / Guest & Staff)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 3. Protected User / Guest Routes (Chatbot Concierge & Profile)
Route::middleware('auth')->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// 4. Hotel Operations & Management Dashboard (Staff & Admin Only)
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/persona', [AdminDashboardController::class, 'updatePersona'])->name('admin.persona.update');
    Route::post('/voice-settings', [AdminDashboardController::class, 'updateVoiceSettings'])->name('admin.voice.update');
    Route::post('/ticket/{ticket}/status', [AdminDashboardController::class, 'updateTicketStatus'])->name('admin.ticket.status');
    Route::post('/escalation/{escalation}/status', [AdminDashboardController::class, 'updateEscalationStatus'])->name('admin.escalation.status');
    Route::post('/booking/{booking}/status', [AdminDashboardController::class, 'updateBookingStatus'])->name('admin.booking.status');

    // Registrasi & Manajemen Akun Tamu oleh Admin (jika tamu belum punya akun)
    Route::post('/guests', [AdminDashboardController::class, 'storeGuest'])->name('admin.guests.store');
    Route::post('/guests/{user}/reset-password', [AdminDashboardController::class, 'resetGuestPassword'])->name('admin.guests.reset-password');
    Route::delete('/guests/{user}', [AdminDashboardController::class, 'destroyGuest'])->name('admin.guests.destroy');

    // PRD Modul 3 (IN-08): Kelola Tipe Kamar & Knowledge Base Hotel
    Route::post('/rooms', [AdminDashboardController::class, 'storeRoom'])->name('admin.rooms.store');
    Route::put('/rooms/{room}', [AdminDashboardController::class, 'updateRoom'])->name('admin.rooms.update');
    Route::delete('/rooms/{room}', [AdminDashboardController::class, 'destroyRoom'])->name('admin.rooms.destroy');

    Route::post('/knowledge', [AdminDashboardController::class, 'storeKnowledge'])->name('admin.knowledge.store');
    Route::put('/knowledge/{knowledge}', [AdminDashboardController::class, 'updateKnowledge'])->name('admin.knowledge.update');
    Route::delete('/knowledge/{knowledge}', [AdminDashboardController::class, 'destroyKnowledge'])->name('admin.knowledge.destroy');
});
