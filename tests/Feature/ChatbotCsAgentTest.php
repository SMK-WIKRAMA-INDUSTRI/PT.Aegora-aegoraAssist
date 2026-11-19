<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotCsAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Aegora Assist');
        $response->assertSee('lang-id-btn');
        $response->assertSee('lang-en-btn');
    }

    public function test_send_chat_message_endpoint_indonesian(): void
    {
        $response = $this->postJson('/api/chat/send', [
            'message' => 'Halo, bagaimana jadwal check-in hotel?',
            'locale' => 'id',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'conversation_id',
                    'session_id',
                    'locale',
                    'user_message' => ['id', 'role', 'content'],
                    'assistant_message' => ['id', 'role', 'content', 'rendered_html', 'source'],
                ],
            ]);

        $this->assertEquals('id', $response->json('data.locale'));
    }

    public function test_send_chat_message_endpoint_english(): void
    {
        $response = $this->postJson('/api/chat/send', [
            'message' => 'Hello, what are your breakfast hours and room rates?',
            'locale' => 'en',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'conversation_id',
                    'session_id',
                    'locale',
                    'user_message',
                    'assistant_message',
                ],
            ]);

        $this->assertEquals('en', $response->json('data.locale'));
    }

    public function test_get_chat_history_endpoint(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session-123',
            'title' => 'Pertanyaan Reservasi',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Apakah sarapan termasuk?',
        ]);

        $response = $this->getJson('/api/chat/history?session_id=test-session-123');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'session_id' => 'test-session-123',
                ],
            ]);
    }
}
