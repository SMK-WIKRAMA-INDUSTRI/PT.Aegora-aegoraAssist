<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\HousekeepingTicket;
use App\Models\RoomOrder;
use App\Models\UrgentEscalation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatbotCsAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Aegora Assist');
        $response->assertSee('Coba Concierge Live');
        $response->assertSee('Portal Staff');
    }

    public function test_chat_page_loads_successfully(): void
    {
        $user = User::create([
            'name' => 'Tamu Hotel',
            'email' => 'tamu@aegora.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/chat');
        $response->assertStatus(200);
        $response->assertSee('Aegora Assist');
        $response->assertSee('lang-id-btn');
        $response->assertSee('booking-modal');
    }

    public function test_staff_login_and_access_admin_dashboard(): void
    {
        $user = User::create([
            'name' => 'Duty Manager',
            'email' => 'admin@aegora.com',
            'password' => Hash::make('password123'),
            'role' => 'staff',
        ]);

        $loginResponse = $this->post('/login', [
            'email' => 'admin@aegora.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        $dashResponse = $this->actingAs($user)->get('/admin');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Hotel Operations', false);
        $dashResponse->assertSee('Pengaturan Gaya Bahasa', false);
    }

    public function test_function_calling_housekeeping_ticket(): void
    {
        $response = $this->postJson('/api/chat/send', [
            'message' => 'Tolong minta tambahan 2 handuk dan sikat gigi ke kamar 304',
            'locale' => 'id',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assistant_message.special_action.type', 'housekeeping_ticket')
            ->assertJsonPath('data.assistant_message.special_action.room', '304');

        $this->assertDatabaseHas('housekeeping_tickets', [
            'room_number' => '304',
        ]);
    }

    public function test_function_calling_fnb_order_with_room_folio(): void
    {
        $response = $this->postJson('/api/chat/send', [
            'message' => 'Pesan 1 Nasi Goreng dan kopi ke kamar 201 sekarang ya',
            'locale' => 'id',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assistant_message.special_action.type', 'room_order')
            ->assertJsonPath('data.assistant_message.special_action.room', '201');

        $this->assertDatabaseHas('room_orders', [
            'room_number' => '201',
            'type' => 'fnb',
            'billed_to_folio' => true,
        ]);
    }

    public function test_sentiment_analysis_urgent_complaint_escalation(): void
    {
        $response = $this->postJson('/api/chat/send', [
            'message' => 'AC kamar 402 saya bocor dan berisik banget, saya kecewa sekali!',
            'locale' => 'id',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assistant_message.special_action.type', 'urgent_escalation')
            ->assertJsonPath('data.assistant_message.special_action.room', '402');

        $this->assertDatabaseHas('urgent_escalations', [
            'room_number' => '402',
            'priority' => 'critical',
            'status' => 'open',
        ]);
    }

    public function test_submit_interactive_booking_form(): void
    {
        $response = $this->postJson('/api/chat/booking', [
            'session_id' => 'test-session-bkg-123',
            'guest_name' => 'Bapak Pratama',
            'guest_phone' => '081298765432',
            'guest_email' => 'pratama@example.com',
            'room_type' => 'Executive Club Suite',
            'check_in_date' => now()->addDay()->format('Y-m-d'),
            'check_out_date' => now()->addDays(3)->format('Y-m-d'),
            'guest_count' => 2,
            'special_requests' => 'Honeymoon room decoration',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.guest_name', 'Bapak Pratama');

        $this->assertDatabaseHas('guest_bookings', [
            'guest_name' => 'Bapak Pratama',
            'guest_phone' => '081298765432',
            'room_type' => 'Executive Club Suite',
            'status' => 'pending_hotel_contact',
        ]);
    }

    public function test_upload_pdf_document_in_chat(): void
    {
        Storage::fake('public');

        $fakePdf = UploadedFile::fake()->create('voucher_reservasi.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/chat/upload-pdf', [
            'file' => $fakePdf,
            'session_id' => 'test-pdf-session',
            'locale' => 'id',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.file_name', 'voucher_reservasi.pdf');

        $this->assertDatabaseHas('chat_documents', [
            'session_id' => 'test-pdf-session',
            'file_name' => 'voucher_reservasi.pdf',
        ]);
    }
}
