<?php

namespace App\Http\Controllers;

use App\Models\ChatDocument;
use App\Models\Conversation;
use App\Models\GuestBooking;
use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use App\Services\DeepgramVoiceService;
use App\Services\NetraChatService;
use App\Services\PdfTextExtractor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected NetraChatService $chatService;
    protected DeepgramVoiceService $voiceService;

    public function __construct(NetraChatService $chatService, DeepgramVoiceService $voiceService)
    {
        $this->chatService = $chatService;
        $this->voiceService = $voiceService;
    }

    /**
     * Show the main interactive AI Concierge Chat view.
     */
    public function index()
    {
        $rooms = Room::where('is_available', true)->orderBy('price_per_night')->get();
        return view('chat', compact('rooms'));
    }

    /**
     * Return active room list from database for interactive booking form & availability calculation.
     */
    public function getRooms(): JsonResponse
    {
        $rooms = Room::where('is_available', true)
            ->orderBy('price_per_night')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'category' => $room->category,
                'price_per_night' => (float) $room->price_per_night,
                'tax_percent' => (float) $room->tax_percent,
                'capacity' => $room->capacity,
                'size_sqm' => $room->size_sqm,
                'bed_type' => $room->bed_type,
                'facilities' => $room->facilities ?? [],
                'image_url' => $room->image_url,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => ['rooms' => $rooms],
        ]);
    }

    /**
     * Handle incoming user message and generate AI response.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:3000',
            'session_id' => 'nullable|string|max:100',
            'locale' => 'nullable|string|max:10',
            'voice_mode' => 'nullable|boolean',
        ]);

        $fallbackLocale = $validated['locale'] ?? 'id';
        $detectedLocale = $this->voiceService->detectLanguage($validated['message'], $fallbackLocale);
        $locale = in_array($detectedLocale, ['id', 'en', 'ja', 'fr', 'de', 'es'], true) ? $detectedLocale : $fallbackLocale;
        $voiceMode = (bool) ($validated['voice_mode'] ?? false);
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

        // Generate response via Service with localized persona, DB Knowledge Base & function calling
        $aiResult = $this->chatService->generateResponse($validated['message'], $history, $locale, $sessionId, $voiceMode);

        // Save Assistant Message
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResult['content'],
            'facility_data' => $aiResult['facility'] ?? null,
        ]);

        $replyLang = $this->voiceService->detectLanguage($assistantMessage->content, $locale);

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
                'session_id' => $conversation->session_id,
                'locale' => $locale,
                'detected_language' => $replyLang,
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
                    'detected_language' => $replyLang,
                    'time' => $assistantMessage->created_at->format('H:i') . ' WIB',
                    'source' => $aiResult['source'] ?? 'simulated',
                ],
            ],
        ]);
    }

    /**
     * Submit Interactive Booking Form directly from Chat (PRD Modul 2: BK-02 s/d BK-07).
     */
    public function submitBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'guest_name' => 'required|string|max:100',
            'guest_phone' => 'required|string|max:30',
            'guest_email' => 'nullable|email|max:100',
            'room_type' => 'required|string',
            'room_count' => 'nullable|integer|min:1|max:5',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'guest_count' => 'required|integer|min:1|max:10',
            'special_requests' => 'nullable|string|max:500',
        ]);

        $roomCount = (int) ($validated['room_count'] ?? 1);
        $checkIn = Carbon::parse($validated['check_in_date']);
        $checkOut = Carbon::parse($validated['check_out_date']);
        $nights = max(1, (int) $checkIn->diffInDays($checkOut));

        // Match Room record in MySQL for authoritative pricing & capacity check
        $room = Room::where('name', $validated['room_type'])->first()
            ?? Room::where('name', 'like', '%' . $validated['room_type'] . '%')->first();

        if ($room && $validated['guest_count'] > ($room->capacity * $roomCount)) {
            return response()->json([
                'status' => 'error',
                'message' => "Kapasitas maksimal untuk {$roomCount}x {$room->name} adalah " . ($room->capacity * $roomCount) . " tamu. Silakan tambah jumlah kamar atau pilih tipe Family Suite.",
            ], 422);
        }

        $pricePerNight = $room
            ? (float) $room->price_per_night
            : (str_contains(strtolower($validated['room_type']), 'suite') ? 2450000 : 1250000);
        $taxPercent = $room ? (float) $room->tax_percent : 21.0;

        $subtotal = $pricePerNight * $nights * $roomCount;
        $taxAmount = round($subtotal * ($taxPercent / 100));
        $depositAmount = 500000 * $roomCount;
        $totalAmount = $subtotal + $taxAmount;

        // Link to authenticated user or existing guest email
        $userId = auth()->id();
        if (!$userId && !empty($validated['guest_email'])) {
            $userId = User::where('email', $validated['guest_email'])->value('id');
        }

        $bookingCode = 'AEG-BKG-' . strtoupper(Str::random(6));

        $booking = GuestBooking::create([
            'user_id' => $userId,
            'room_id' => $room?->id,
            'booking_code' => $bookingCode,
            'session_id' => $validated['session_id'],
            'guest_name' => $validated['guest_name'],
            'guest_phone' => $validated['guest_phone'],
            'guest_email' => $validated['guest_email'] ?? null,
            'room_type' => $room ? $room->name : $validated['room_type'],
            'room_count' => $roomCount,
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'guest_count' => $validated['guest_count'],
            'price_per_night' => $pricePerNight,
            'nights' => $nights,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'deposit_amount' => $depositAmount,
            'total_amount' => $totalAmount,
            'special_requests' => $validated['special_requests'] ?? null,
            'status' => 'pending_hotel_contact',
            'payment_status' => 'unpaid',
        ]);

        $fmtTotal = 'IDR ' . number_format($totalAmount, 0, ',', '.');
        $fmtSubtotal = 'IDR ' . number_format($subtotal, 0, ',', '.');
        $fmtTax = 'IDR ' . number_format($taxAmount, 0, ',', '.');

        // Tambahkan konfirmasi lengkap PRD 4.4 ke dalam riwayat obrolan
        $conversation = Conversation::where('session_id', $validated['session_id'])->first();
        if ($conversation) {
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => "✅ **Konfirmasi Pemesanan Kamar Aegora Hotel**\n\n"
                    . "• **Kode Booking:** `{$bookingCode}`\n"
                    . "• **Nama Tamu:** {$booking->guest_name} ({$booking->guest_phone})\n"
                    . "• **Tipe Kamar:** {$booking->room_count}x {$booking->room_type} ({$booking->guest_count} tamu)\n"
                    . "• **Tanggal:** " . $booking->check_in_date->format('d M Y') . " s/d " . $booking->check_out_date->format('d M Y') . " ({$nights} malam)\n"
                    . "• **Rincian Biaya:** Kamar {$fmtSubtotal} + Pajak & Layanan {$fmtTax} = **{$fmtTotal}**\n"
                    . "• **Status Pembayaran:** Menunggu Konfirmasi Pembayaran\n"
                    . "• **Kebijakan Pembatalan:** Pembatalan gratis hingga 24 jam sebelum check-in.\n\n"
                    . "*Tim Reservasi Aegora Hotel akan segera menghubungi WhatsApp Anda ({$booking->guest_phone}) untuk langkah pembayaran. Simpan kode booking Anda untuk verifikasi saat check-in.*",
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'booking_code' => $booking->booking_code,
                'guest_name' => $booking->guest_name,
                'room_type' => $booking->room_type,
                'room_count' => $booking->room_count,
                'guest_count' => $booking->guest_count,
                'nights' => $nights,
                'check_in' => $booking->check_in_date->format('d M Y'),
                'check_out' => $booking->check_out_date->format('d M Y'),
                'phone' => $booking->guest_phone,
                'subtotal_formatted' => $fmtSubtotal,
                'tax_formatted' => $fmtTax,
                'total_formatted' => $fmtTotal,
                'payment_status' => 'Menunggu Konfirmasi',
                'cancellation_policy' => 'Gratis pembatalan hingga 24 jam sebelum check-in (H-1 pukul 14.00 WIB).',
                'special_requests' => $booking->special_requests,
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
