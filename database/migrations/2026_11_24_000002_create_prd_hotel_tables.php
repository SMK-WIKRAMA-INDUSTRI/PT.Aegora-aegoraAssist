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
        // PRD Modul 2 & 3: Data Tipe Kamar Resmi Hotel (dapat dikelola Admin)
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 50)->default('deluxe'); // deluxe, suite, family
            $table->text('description')->nullable();
            $table->decimal('price_per_night', 12, 2);
            $table->decimal('tax_percent', 5, 2)->default(21.00); // Pajak & layanan 21%
            $table->unsignedInteger('capacity')->default(2);
            $table->unsignedInteger('size_sqm')->default(36);
            $table->string('bed_type', 100)->default('King Bed');
            $table->unsignedInteger('total_units')->default(10);
            $table->json('facilities')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        // PRD Modul 3 (IN-08): Knowledge Base Informasi Hotel (Fasilitas, Jam, Kebijakan, FAQ)
        Schema::create('hotel_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // profile, facility, restaurant, hours, policy, transport, contact, faq
            $table->string('title');
            $table->string('location')->nullable();
            $table->string('operating_hours')->nullable();
            $table->string('extra_fee')->nullable();
            $table->text('content');
            $table->text('content_en')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // PRD Modul 2 & 4: Perluasan kolom reservasi untuk rincian tagihan, check-in/out & relasi tamu
        Schema::table('guest_bookings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->after('user_id')->constrained('rooms')->nullOnDelete();
            $table->string('room_number', 20)->nullable()->after('room_type');
            $table->unsignedInteger('room_count')->default(1)->after('room_number');
            $table->decimal('price_per_night', 12, 2)->default(0)->after('guest_count');
            $table->unsignedInteger('nights')->default(1)->after('price_per_night');
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('nights');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal_amount');
            $table->decimal('deposit_amount', 12, 2)->default(500000)->after('tax_amount');
            $table->decimal('total_amount', 12, 2)->default(0)->after('deposit_amount');
            $table->string('payment_status', 30)->default('unpaid')->after('status'); // unpaid, dp_paid, paid, refunded
            $table->timestamp('checked_in_at')->nullable()->after('payment_status');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('room_id');
            $table->dropColumn([
                'room_number',
                'room_count',
                'price_per_night',
                'nights',
                'subtotal_amount',
                'tax_amount',
                'deposit_amount',
                'total_amount',
                'payment_status',
                'checked_in_at',
                'checked_out_at',
            ]);
        });

        Schema::dropIfExists('hotel_knowledge');
        Schema::dropIfExists('rooms');
    }
};
