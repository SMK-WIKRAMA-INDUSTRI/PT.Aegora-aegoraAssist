<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAuthAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_registration_creates_account_and_redirects_to_chat(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'phone' => '08123456789',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/chat');

        $this->assertDatabaseHas('users', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'role' => 'user',
        ]);

        $this->assertAuthenticated();
    }

    public function test_user_login_redirects_to_chat(): void
    {
        $user = User::create([
            'name' => 'Rina Wijaya',
            'email' => 'rina@example.com',
            'phone' => '08987654321',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'email' => 'rina@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/chat');
        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_login_redirects_to_admin_dashboard(): void
    {
        $staff = User::create([
            'name' => 'Staff Front Desk',
            'email' => 'admin@aegora.com',
            'password' => Hash::make('password123'),
            'role' => 'staff',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@aegora.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($staff);
    }

    public function test_unauthenticated_guest_is_redirected_to_login_when_accessing_chat(): void
    {
        $response = $this->get('/chat');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_profile_and_view_chat_history(): void
    {
        $user = User::create([
            'name' => 'Maya Sari',
            'email' => 'maya@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $conv = Conversation::create([
            'user_id' => $user->id,
            'session_id' => 'maya_session_123',
            'title' => 'Tanya Kamar Deluxe',
        ]);

        Message::create([
            'conversation_id' => $conv->id,
            'role' => 'user',
            'content' => 'Berapa harga kamar Deluxe per malam?',
        ]);

        Message::create([
            'conversation_id' => $conv->id,
            'role' => 'assistant',
            'content' => 'Tarif Deluxe mulai Rp 1.250.000 net/malam.',
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Maya Sari');
        $response->assertSee('Tanya Kamar Deluxe');
        $response->assertSee('maya_session_123');
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
            'phone' => '0811111111',
            'password' => Hash::make('oldpassword'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post('/profile', [
            'name' => 'Nama Baru Hendra',
            'phone' => '0822222222',
            'email' => 'baru@example.com',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru Hendra',
            'phone' => '0822222222',
            'email' => 'baru@example.com',
        ]);
    }

    public function test_user_message_links_to_authenticated_user_id(): void
    {
        $user = User::create([
            'name' => 'Guest Tester',
            'email' => 'tester@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->postJson('/api/chat/send', [
            'message' => 'Halo Concierge, ada kolam renang tidak?',
            'session_id' => 'auth_session_test',
            'locale' => 'id',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('conversations', [
            'session_id' => 'auth_session_test',
            'user_id' => $user->id,
        ]);
    }
}
