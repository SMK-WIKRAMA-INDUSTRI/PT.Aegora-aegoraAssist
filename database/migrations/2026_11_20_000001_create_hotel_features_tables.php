<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hotel Settings & AI Persona Configuration
        Schema::create('hotel_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Housekeeping Tickets (Permintaan Fasilitas Instan)
        Schema::create('housekeeping_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->string('session_id')->nullable();
            $table->string('room_number')->nullable();
            $table->text('items');
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Room Orders (F&B and Spa with Room Folio Billing)
        Schema::create('room_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('session_id')->nullable();
            $table->string('room_number')->nullable();
            $table->string('type'); // fnb, spa
            $table->json('items_json');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->boolean('billed_to_folio')->default(true);
            $table->string('status')->default('confirmed'); // confirmed, preparing, delivered, completed
            $table->timestamps();
        });

        // Guest Bookings (Form Booking Tamu Interaktif)
        Schema::create('guest_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->string('session_id')->nullable();
            $table->string('guest_name');
            $table->string('guest_phone');
            $table->string('guest_email')->nullable();
            $table->string('room_type');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('guest_count')->default(2);
            $table->text('special_requests')->nullable();
            $table->string('status')->default('pending_hotel_contact'); // pending_hotel_contact, confirmed, cancelled
            $table->timestamps();
        });

        // Urgent Escalations (Deteksi Komplain Berat & Sentimen Marah)
        Schema::create('urgent_escalations', function (Blueprint $table) {
            $table->id();
            $table->string('escalation_code')->unique();
            $table->string('session_id')->nullable();
            $table->string('room_number')->nullable();
            $table->text('issue_summary');
            $table->string('sentiment')->default('urgent_complaint');
            $table->string('priority')->default('high'); // high, critical
            $table->string('status')->default('open'); // open, in_progress, resolved
            $table->timestamps();
        });

        // Chat Documents (Upload PDF file di chat)
        Schema::create('chat_documents', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->longText('extracted_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_documents');
        Schema::dropIfExists('urgent_escalations');
        Schema::dropIfExists('guest_bookings');
        Schema::dropIfExists('room_orders');
        Schema::dropIfExists('housekeeping_tickets');
        Schema::dropIfExists('hotel_settings');
    }
};
