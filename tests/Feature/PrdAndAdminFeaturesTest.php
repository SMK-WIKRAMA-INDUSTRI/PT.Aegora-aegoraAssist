<?php

namespace Tests\Feature;

use App\Models\GuestBooking;
use App\Models\HotelKnowledge;
use App\Models\Room;
use App\Models\RoomOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrdAndAdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function createStaff(): User
    {
        return User::create([
            'name' => 'Admin Aegora',
            'email' => 'admin@aegora.com',
            'phone' => '+62 811-9876-5432',
            'password' => Hash::make('password123'),
            'role' => 'staff',
        ]);
    }

    protected function createGuest(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Bpk. Hendra Wijaya',
            'email' => 'tamu@aegora.com',
            'phone' => '+62 812-3456-7890',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ], $overrides));
    }

    public function test_login_and_register_views_have_password_hide_show_toggles(): void
    {
        $login = $this->get('/login');
        $login->assertStatus(200);
        $login->assertSee('togglePasswordVisibility', false);
        $login->assertSee('eye-open', false);
        $login->assertSee('eye-closed', false);

        $register = $this->get('/register');
        $register->assertStatus(200);
        $register->assertSee("togglePasswordVisibility('password'", false);
        $register->assertSee("togglePasswordVisibility('password_confirmation'", false);
    }

    public function test_admin_can_register_guest_account_and_link_existing_booking(): void
    {
        $staff = $this->createStaff();

        // Guest created a booking before having an account
        $booking = GuestBooking::create([
            'booking_code' => 'AEG-BKG-TEST99',
            'guest_name' => 'Ibu Sinta Dewi',
            'guest_phone' => '081355556666',
            'guest_email' => 'sinta@example.com',
            'room_type' => 'Deluxe King Room',
            'check_in_date' => now()->addDay()->format('Y-m-d'),
            'check_out_date' => now()->addDays(3)->format('Y-m-d'),
            'guest_count' => 2,
            'status' => 'pending_hotel_contact',
        ]);

        $response = $this->actingAs($staff)->post('/admin/guests', [
            'name' => 'Ibu Sinta Dewi',
            'phone' => '081355556666',
            'email' => 'sinta@example.com',
            'password' => 'Aegora2026!',
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionHas('created_guest_credentials');

        $guest = User::where('email', 'sinta@example.com')->first();
        $this->assertNotNull($guest);
        $this->assertEquals('user', $guest->role);

        // Booking should now be linked to the newly created guest account
        $this->assertEquals($guest->id, $booking->fresh()->user_id);

        // Newly registered guest can log in immediately and access /chat
        $this->post('/logout');
        $loginResp = $this->post('/login', [
            'email' => 'sinta@example.com',
            'password' => 'Aegora2026!',
        ]);
        $loginResp->assertRedirect('/chat');
        $this->assertAuthenticatedAs($guest);
    }

    public function test_regular_guest_cannot_access_admin_dashboard_or_register_guests(): void
    {
        $guest = $this->createGuest();

        $this->actingAs($guest)->get('/admin')->assertStatus(403);
        $this->actingAs($guest)->post('/admin/guests', [
            'name' => 'Unauthorized',
            'phone' => '081200001111',
            'email' => 'unauth@example.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_admin_can_manage_rooms_and_knowledge_base_used_by_ai_chat(): void
    {
        $staff = $this->createStaff();

        $this->actingAs($staff)->post('/admin/rooms', [
            'name' => 'Royal Lagoon Suite',
            'category' => 'suite',
            'price_per_night' => 3800000,
            'capacity' => 4,
            'size_sqm' => 80,
            'bed_type' => '1 Super King Bed',
            'total_units' => 3,
            'facilities_text' => 'Private Pool, Bathtub, Sarapan',
            'description' => 'Suite tepi laguna eksklusif',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', ['name' => 'Royal Lagoon Suite']);

        $this->actingAs($staff)->post('/admin/knowledge', [
            'category' => 'policy',
            'title' => 'Kebijakan Hewan Peliharaan & Merokok',
            'content' => 'Hewan peliharaan belum diperkenankan di area hotel demi kenyamanan tamu.',
            'content_en' => 'Pets are not permitted in the hotel area.',
        ])->assertSessionHas('success');

        // Chat immediately answers from live DB Rooms & Knowledge Base
        $roomResp = $this->postJson('/api/chat/send', [
            'message' => 'Ada kamar kosong dan berapa harganya?',
            'locale' => 'id',
        ]);
        $roomResp->assertStatus(200);
        $this->assertStringContainsString('Royal Lagoon Suite', $roomResp->json('data.assistant_message.content'));

        $policyResp = $this->postJson('/api/chat/send', [
            'message' => 'Boleh bawa kucing ke hotel?',
            'locale' => 'id',
        ]);
        $policyResp->assertStatus(200);
        $this->assertStringContainsString('Hewan peliharaan belum diperkenankan', $policyResp->json('data.assistant_message.content'));
    }

    public function test_prd_module_4_reservation_and_billing_requires_identity_verification(): void
    {
        $room = Room::create([
            'name' => 'Deluxe King Room',
            'slug' => 'deluxe-king-test',
            'category' => 'deluxe',
            'price_per_night' => 1250000,
            'tax_percent' => 21,
            'capacity' => 2,
            'size_sqm' => 36,
            'bed_type' => '1 King Bed',
            'total_units' => 5,
        ]);

        GuestBooking::create([
            'room_id' => $room->id,
            'booking_code' => 'AEG-BKG-778899',
            'session_id' => 'verify_sess',
            'guest_name' => 'Bpk. Hendra Wijaya',
            'guest_phone' => '081234567890',
            'guest_email' => 'tamu@aegora.com',
            'room_type' => 'Deluxe King Room',
            'room_number' => '304',
            'room_count' => 1,
            'check_in_date' => now()->format('Y-m-d'),
            'check_out_date' => now()->addDays(2)->format('Y-m-d'),
            'guest_count' => 2,
            'price_per_night' => 1250000,
            'nights' => 2,
            'subtotal_amount' => 2500000,
            'tax_amount' => 525000,
            'deposit_amount' => 500000,
            'total_amount' => 3025000,
            'status' => 'confirmed',
            'payment_status' => 'dp_paid',
        ]);

        RoomOrder::create([
            'order_code' => 'FNB-TEST1',
            'session_id' => 'verify_sess',
            'room_number' => '304',
            'type' => 'fnb',
            'items_json' => ['request_text' => 'Nasi Goreng'],
            'total_amount' => 150000,
            'billed_to_folio' => true,
            'status' => 'delivered',
        ]);

        // 1. Asking for bill without credentials -> prompts verification, leaks nothing
        $prompt = $this->postJson('/api/chat/send', [
            'message' => 'Tagihan saya berapa ya?',
            'session_id' => 'guest_check_1',
        ]);
        $prompt->assertStatus(200)
            ->assertJsonPath('data.assistant_message.special_action.type', 'verification_required');
        $this->assertStringNotContainsString('3.175.000', $prompt->json('data.assistant_message.content'));

        // 2. Wrong last name with valid booking code -> fails verification, leaks nothing
        $wrong = $this->postJson('/api/chat/send', [
            'message' => 'Cek tagihan AEG-BKG-778899 Santoso',
            'session_id' => 'guest_check_2',
        ]);
        $wrong->assertStatus(200)
            ->assertJsonPath('data.assistant_message.special_action.type', 'verification_failed');
        $this->assertStringNotContainsString('Hendra Wijaya', $wrong->json('data.assistant_message.content'));

        // 3. Matching booking code + last name -> verified & displays full folio including room order!
        $verified = $this->postJson('/api/chat/send', [
            'message' => 'Cek tagihan AEG-BKG-778899 Wijaya',
            'session_id' => 'guest_check_3',
        ]);
        $verified->assertStatus(200)
            ->assertJsonPath('data.assistant_message.special_action.type', 'reservation_detail');
        $content = $verified->json('data.assistant_message.content');
        $this->assertStringContainsString('Hendra Wijaya', $content);
        $this->assertStringContainsString('3.175.000', $content);
    }

    public function test_deepgram_voice_config_and_tts_stt_endpoints(): void
    {
        // 1. Public voice config returns presets without leaking API key
        $cfg = $this->getJson('/api/voice/config');
        $cfg->assertStatus(200)
            ->assertJsonPath('data.provider', 'deepgram')
            ->assertJsonStructure(['data' => ['configured', 'active_voices', 'voices']]);

        // 2. Without API key, /api/voice/speak returns clean JSON fallback
        config(['deepgram.api_key' => '']);
        $fallback = $this->postJson('/api/voice/speak', [
            'text' => 'Halo, selamat datang di Aegora Hotel!',
        ]);
        $fallback->assertStatus(200)
            ->assertJsonPath('status', 'fallback')
            ->assertJsonPath('configured', false)
            ->assertJsonPath('data.detected_language', 'id');

        // 3. With API key configured, Indonesian uses Deepgram Flux /v2/speak & English uses Aura-2 /v1/speak
        config(['deepgram.api_key' => 'dg_test_fake_key_123']);
        $fakeMp3 = str_repeat('ID3FAKEAUDIO', 30);

        Http::fake([
            'https://api.deepgram.com/v2/speak*' => Http::response($fakeMp3, 200, ['Content-Type' => 'audio/mpeg']),
            'https://api.deepgram.com/v1/speak*' => Http::response($fakeMp3, 200, ['Content-Type' => 'audio/mpeg']),
            'https://api.deepgram.com/v1/listen*' => Http::response([
                'results' => [
                    'channels' => [[
                        'detected_language' => 'en',
                        'alternatives' => [[
                            'transcript' => 'Do you have a swimming pool?',
                            'confidence' => 0.98,
                        ]],
                    ]],
                ],
            ], 200),
        ]);

        $idTts = $this->post('/api/voice/speak', [
            'text' => 'Selamat siang Bapak, ada yang bisa saya bantu hari ini?',
        ]);
        $idTts->assertStatus(200)
            ->assertHeader('X-Voice-Provider', 'deepgram')
            ->assertHeader('X-Detected-Language', 'id')
            ->assertHeader('X-Voice-Model', 'flux-fitri-id');

        $enTts = $this->post('/api/voice/speak', [
            'text' => 'Welcome to Aegora Hotel! How may I assist you today?',
        ]);
        $enTts->assertStatus(200)
            ->assertHeader('X-Voice-Provider', 'deepgram')
            ->assertHeader('X-Detected-Language', 'en')
            ->assertHeader('X-Voice-Model', 'aura-2-helena-en');

        // 4. Full Deepgram STS converse endpoint auto-detects English & replies in English with audio!
        $fakeAudio = UploadedFile::fake()->create('guest.webm', 50, 'audio/webm');
        $sts = $this->post('/api/voice/converse', [
            'audio' => $fakeAudio,
            'session_id' => 'voice_sts_test',
        ]);
        $sts->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.detected_language', 'en')
            ->assertJsonPath('data.transcript', 'Do you have a swimming pool?')
            ->assertJsonPath('data.audio.provider', 'deepgram')
            ->assertJsonPath('data.audio.model', 'aura-2-helena-en');
    }
}
