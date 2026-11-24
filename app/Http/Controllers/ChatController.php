<?php

namespace App\Http\Controllers;

use App\Models\ChatDocument;
use App\Models\Conversation;
use App\Models\GuestBooking;
use App\Models\Message;
use App\Services\NetraChatService;
use App\Services\PdfTextExtractor;
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
     * Show the main interactive AI Concierge Chat view.
     */
    public function index()
    {
        return view('chat');
    }

    /**
     * Handle incoming user message and generate AI response.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:3000',
            'session_id' => 'nullable|string|max:100',
            'locale' => 'nullable|string|in:id,en',
        ]);

        $locale = $validated['locale'] ?? 'id';
        $sessionId = $validated['session_id'] ?? (string) Str::uuid();

        // Retrieve or initialize conversation
        $conversation = Conversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'title' => Str::limit($validated['message'], 40),
                'user_id' => auth()->id() ?? null,
            ]
        );

        if (auth()->check() && !$conversation->user_id) {
            $conversation->user_id = auth()->id();
            $conversation->save();
        }

        // Save User Message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // Load conversation context history
        $history = $conversation->messages()
            ->latest('id')
            ->take(12)
            ->get()
            ->reverse()
            ->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        // Generate response via Service with localized persona & function calling
        $aiResult = $this->chatService->generateResponse($validated['message'], $history, $locale, $sessionId);

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
                'locale' => $locale,
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
                    'special_action' => $aiResult['special_action'] ?? null,
                    'time' => $assistantMessage->created_at->format('H:i') . ' WIB',
                    'source' => $aiResult['source'] ?? 'simulated',
                ],
            ],
        ]);
    }

    /**
     * Submit Interactive Booking Form directly from Chat.
     */
    public function submitBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'guest_name' => 'required|string|max:100',
            'guest_phone' => 'required|string|max:30',
            'guest_email' => 'nullable|email|max:100',
            'room_type' => 'required|string',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'guest_count' => 'required|integer|min:1|max:10',
            'special_requests' => 'nullable|string|max:500',
        ]);

        $bookingCode = 'AEG-BKG-' . strtoupper(Str::random(6));

        $booking = GuestBooking::create([
            'booking_code' => $bookingCode,
            'session_id' => $validated['session_id'],
            'guest_name' => $validated['guest_name'],
            'guest_phone' => $validated['guest_phone'],
            'guest_email' => $validated['guest_email'] ?? null,
            'room_type' => $validated['room_type'],
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'guest_count' => $validated['guest_count'],
            'special_requests' => $validated['special_requests'] ?? null,
            'status' => 'pending_hotel_contact',
        ]);

        // Tambahkan konfirmasi ke dalam riwayat obrolan
        $conversation = Conversation::where('session_id', $validated['session_id'])->first();
        if ($conversation) {
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => "✅ **Formulir Booking Berhasil Terkirim!**\n\nKode Booking Resmi Anda: **`{$bookingCode}`**\nNama Tamu: **{$booking->guest_name}**\nTipe Kamar: **{$booking->room_type}**\nTanggal: **" . $booking->check_in_date->format('d M Y') . " s/d " . $booking->check_out_date->format('d M Y') . "**\n\n*Pihak Hotel Aegora akan segera menghubungi WhatsApp Anda ({$booking->guest_phone}) untuk konfirmasi pembayaran dan rincian kamar.*",
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'booking_code' => $booking->booking_code,
                'guest_name' => $booking->guest_name,
                'room_type' => $booking->room_type,
                'check_in' => $booking->check_in_date->format('d M Y'),
                'check_out' => $booking->check_out_date->format('d M Y'),
                'phone' => $booking->guest_phone,
            ],
            'message' => 'Reservasi kamar berhasil dicatat! Pihak hotel akan segera menghubungi Anda.',
        ]);
    }

    /**
     * Handle PDF Document Upload in Chat (e.g., Guest voucher, ID card, booking note).
     */
    public function uploadPdf(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
            'session_id' => 'required|string',
            'locale' => 'nullable|string|in:id,en',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $storedPath = $file->store('chat_docs', 'public');
        $fullPath = storage_path('app/public/' . $storedPath);

        // Ekstraksi teks dari PDF
        $extractedText = PdfTextExtractor::extract($fullPath);

        ChatDocument::create([
            'session_id' => $request->input('session_id'),
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'extracted_text' => $extractedText,
        ]);

        // Simpan pesan sistem bahwa dokumen diunggah
        $conversation = Conversation::where('session_id', $request->input('session_id'))->first();
        if ($conversation) {
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => "📄 [Mengunggah Dokumen PDF: {$fileName}]",
            ]);
        }

        // Generate respons analisis PDF oleh AI
        $prompt = "Tamu telah mengunggah dokumen PDF dengan nama '{$fileName}'. Isi teks yang terbaca:\n" . Str::limit($extractedText, 1500) . "\nBerikan konfirmasi hangat bahwa dokumen telah diterima dan sebutkan ringkasannya.";
        $aiResult = $this->chatService->generateResponse($prompt, [], $request->input('locale', 'id'), $request->input('session_id'));

        if ($conversation) {
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $aiResult['content'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'file_name' => $fileName,
                'file_url' => asset('storage/' . $storedPath),
                'assistant_reply' => $aiResult['content'],
                'rendered_html' => Str::markdown($aiResult['content']),
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
                'data' => ['messages' => []],
            ]);
        }

        $conversation = Conversation::where('session_id', $sessionId)
            ->with(['messages'])
            ->first();

        if (!$conversation) {
            return response()->json([
                'status' => 'success',
                'data' => ['messages' => []],
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
     * Retrieve conversation sessions of the authenticated user.
     */
    public function getUserConversations(): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([
                'status' => 'success',
                'data' => ['conversations' => []],
            ]);
        }

        $conversations = Conversation::where('user_id', $userId)
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->take(25)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'session_id' => $c->session_id,
                    'title' => $c->title,
                    'messages_count' => $c->messages_count,
                    'updated_at' => $c->updated_at->diffForHumans(),
                    'date' => $c->updated_at->format('d M Y, H:i'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversations' => $conversations,
            ],
        ]);
    }

    /**
     * Create a fresh conversation session.
     */
    public function newConversation(): JsonResponse
    {
        $session = 'aegora_' . Str::random(12) . '_' . time();
        $conversation = Conversation::create([
            'session_id' => $session,
            'title' => 'Sesi Percakapan Baru',
            'user_id' => auth()->id() ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'session_id' => $conversation->session_id,
            ],
        ]);
    }
}
