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
    Route::post('/ticket/{ticket}/status', [AdminDashboardController::class, 'updateTicketStatus'])->name('admin.ticket.status');
    Route::post('/escalation/{escalation}/status', [AdminDashboardController::class, 'updateEscalationStatus'])->name('admin.escalation.status');
});
