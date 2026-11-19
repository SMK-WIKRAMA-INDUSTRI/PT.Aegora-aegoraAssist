<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\NetraChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected NetraChatService $chatService;

    public function __construct(NetraChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Handle incoming user message and generate AI response.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:2000',
            'session_id' => 'nullable|string|max:100',
        ]);

        $sessionId = $validated['session_id'] ?? (string) Str::uuid();

        // Retrieve or initialize conversation
        $conversation = Conversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'title' => Str::limit($validated['message'], 40),
                'user_id' => auth()->id() ?? null,
            ]
        );

        // Save User Message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // Load conversation context history
        $history = $conversation->messages()
            ->latest('id')
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        // Generate response via Service
        $aiResult = $this->chatService->generateResponse($validated['message'], $history);

        // Save Assistant Message
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResult['content'],
            'facility_data' => $aiResult['facility'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
                'session_id' => $conversation->session_id,
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'content' => $userMessage->content,
                    'time' => $userMessage->created_at->format('H:i') . ' WIB',
                ],
                'assistant_message' => [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $assistantMessage->content,
                    'rendered_html' => Str::markdown($assistantMessage->content),
                    'facility' => $assistantMessage->facility_data,
                    'time' => $assistantMessage->created_at->format('H:i') . ' WIB',
                    'source' => $aiResult['source'] ?? 'simulated',
                ],
            ],
        ]);
    }

    /**
     * Retrieve messages for an active session.
     */
    public function getHistory(Request $request): JsonResponse
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'messages' => [],
                ],
            ]);
        }

        $conversation = Conversation::where('session_id', $sessionId)
            ->with(['messages'])
            ->first();

        if (!$conversation) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'messages' => [],
                ],
            ]);
        }

        $messages = $conversation->messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'rendered_html' => Str::markdown($msg->content),
                'facility' => $msg->facility_data,
                'time' => $msg->created_at->format('H:i') . ' WIB',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
                'session_id' => $conversation->session_id,
                'title' => $conversation->title,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Create a fresh conversation session.
     */
    public function newConversation(): JsonResponse
    {
        $sessionId = (string) Str::uuid();
        $conversation = Conversation::create([
            'session_id' => $sessionId,
            'title' => 'Percakapan Baru',
            'user_id' => auth()->id() ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
                'session_id' => $conversation->session_id,
            ],
        ]);
    }

    /**
     * List recent conversations.
     */
    public function listConversations(): JsonResponse
    {
        $conversations = Conversation::latest('updated_at')
            ->take(10)
            ->get(['id', 'session_id', 'title', 'created_at', 'updated_at']);

        return response()->json([
            'status' => 'success',
            'data' => $conversations,
        ]);
    }
}
