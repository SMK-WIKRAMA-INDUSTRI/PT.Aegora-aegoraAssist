<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

// 1. Luxury Landing Page (Hotel Showcase & Investor Ready)
Route::get('/', [LandingController::class, 'index'])->name('landing');

// 2. AI Concierge Chatbot Page
Route::get('/chat', [ChatController::class, 'index'])->name('chat');

// 3. Staff & Admin Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 4. Hotel Operations & Management Dashboard
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/persona', [AdminDashboardController::class, 'updatePersona'])->name('admin.persona.update');
    Route::post('/ticket/{ticket}/status', [AdminDashboardController::class, 'updateTicketStatus'])->name('admin.ticket.status');
    Route::post('/escalation/{escalation}/status', [AdminDashboardController::class, 'updateEscalationStatus'])->name('admin.escalation.status');
});
