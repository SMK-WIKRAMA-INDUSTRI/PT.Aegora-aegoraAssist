<?php

namespace App\Services;

use App\Models\ChatDocument;
use App\Models\HotelSetting;
use App\Models\HousekeepingTicket;
use App\Models\RoomOrder;
use App\Models\UrgentEscalation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class NetraChatService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected float $temperature;
    protected int $maxTokens;
    protected string $systemPrompt;

    public function __construct()
    {
        $this->apiKey = trim(config('netra.api_key', ''));
        $this->baseUrl = rtrim(config('netra.base_url', 'https://api.netraruntime.com/v1'), '/');
        $this->model = config('netra.model', 'deepseek/deepseek-v4.1-flash');
        $this->timeout = (int) config('netra.timeout', 30);
        $this->temperature = (float) config('netra.temperature', 0.7);
        $this->maxTokens = (int) config('netra.max_tokens', 1500);
        $this->systemPrompt = config('netra.system_prompt', '');
    }

    /**
     * Generate response with Intelligent Dispatching & Function Calling.
     *
     * @param string $message
     * @param array $history
     * @param string $locale ('id' or 'en')
     * @param string|null $sessionId
     * @return array{content: string, facility: array|null, special_action: array|null, source: string}
     */
    public function generateResponse(string $message, array $history = [], string $locale = 'id', ?string $sessionId = null): array
    {
        // 1. Deteksi Emosi & Komplain Berat (Sentiment Analysis & Urgent Escalation)
        $complaintAction = $this->detectUrgentComplaint($message, $sessionId, $locale);
        if ($complaintAction) {
            return $complaintAction;
        }

        // 2. Deteksi Permintaan Fasilitas Instan (Housekeeping Ticket)
        $hkAction = $this->detectHousekeepingRequest($message, $sessionId, $locale);
        if ($hkAction) {
            return $hkAction;
        }

        // 3. Deteksi Pemesanan F&B dan Spa Terintegrasi (Folio Billing)
        $orderAction = $this->detectRoomOrderRequest($message, $sessionId, $locale);
        if ($orderAction) {
            return $orderAction;
        }

        // 4. Deteksi Permintaan Booking Kamar (Tampilkan Interactive Booking Form)
        $bookingAction = $this->detectBookingIntent($message, $sessionId, $locale);
        if ($bookingAction) {
            return $bookingAction;
        }

        // 5. Cek Dokumen PDF Konteks RAG (jika ada file terunggah pada session ini)
        $documentContext = $this->getDocumentContext($sessionId);

        // 6. Hubungi Netra Runtime (Model DeepSeek) jika API Key tersedia
        if (!empty($this->apiKey) && $this->apiKey !== 'YOUR_NETRA_API_KEY_HERE') {
            try {
                $response = $this->callNetraApi($message, $history, $locale, $documentContext);
                if ($response) {
                    return [
                        'content' => $response,
                        'facility' => $this->detectFacilityAttachment($message, $response, $locale),
                        'special_action' => null,
                        'source' => 'netra_deepseek'
                    ];
                }
            } catch (Throwable $e) {
                Log::warning('Netra Runtime (DeepSeek) call error: ' . $e->getMessage());
            }
        }

        // 7. Fallback Cerdas Kontekstual & Non-repetitif
        $simulated = $this->generateSimulatedHotelResponse($message, $locale, $history);
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_NETRA_API_KEY_HERE') {
            $simulated['source'] = 'simulated_no_key';
        }

        return $simulated;
    }

    /**
     * Get system prompt customized by Persona and Language.
     */
    protected function getLocalizedSystemPrompt(string $locale, bool $isFollowUp = false): string
    {
        $persona = HotelSetting::getVal('ai_persona', 'jakarta_business');
        $hotelName = HotelSetting::getVal('hotel_name', 'Aegora Hotel & Hospitality');

        $styleInstruction = "";
        if ($persona === 'bali_resort') {
            $styleInstruction = "Gaya bahasa: Santai, hangat, akrab, ramah pulau Bali ('Halo Kak!', 'Selamat datang di surga peristirahatan kami...'). Berikan sentuhan keramahan tropis yang menyenangkan dan santai.";
        } elseif ($persona === 'luxury_heritage') {
            $styleInstruction = "Gaya bahasa: Anggun, berkelas, penuh tata krama nusantara, bertutur kata sangat santun dan elegan menghormati tamu selayaknya bangsawan.";
        } else {
            // jakarta_business
            $styleInstruction = "Gaya bahasa: Modern, formal, efisien, profesional hotel bintang lima di pusat kota Jakarta. Tanggap, solutif, lugas tanpa bertele-tele.";
        }

        $avoidRepetitionRule = $isFollowUp 
            ? "PENTING: Percakapan sedang berlangsung! JANGAN mengulang salam perkenalan formal ('Halo, saya Aegora Assist...') lagi. Langsung jawab pertanyaan tamu secara to-the-point, jelas, dan ramah sesuai konteks obrolan."
            : "Sapa tamu dengan ramah dan perkenalkan diri secara ringkas di awal.";

        if ($locale === 'en') {
            return "You are 'Aegora Assist', the 5-star Customer Service Agent & AI Concierge at {$hotelName}.\n" .
                "Hotel Persona Style: {$styleInstruction}\n" .
                "{$avoidRepetitionRule}\n\n" .
                "Guidelines:\n" .
                "1. Provide accurate information about room reservations (Deluxe King/Twin from IDR 1,250,000++, Executive Club Suite from IDR 2,450,000++), The Verandah Restaurant (06:30 - 10:30 WIB), Level 3 Outdoor Infinity Pool, Level 5 Spa & Gym, express check-in (14:00) & check-out (12:00), airport shuttles, and Wi-Fi.\n" .
                "2. Multilingual & Dialects: Automatically understand and reply in the guest's language (English, Indonesian, Mandarin, Japanese, Korean, Arabic, or local Indonesian dialects like Javanese or Balinese) naturally.\n" .
                "3. Proactive Upselling: If the guest mentions celebrating a birthday, honeymoon, or anniversary, kindly offer romantic room decoration setup (roses & artisanal cake) or an exclusive Executive Suite upgrade.\n" .
                "4. Use clean Markdown formatting with bullet points and bold highlights.\n" .
                "5. We do not do direct check-in via chat; instead guide them to our interactive booking form.";
        }

        return "Anda adalah 'Aegora Assist', Customer Service Agent & AI Concierge profesional di {$hotelName}.\n" .
            "Gaya Kepribadian Hotel: {$styleInstruction}\n" .
            "{$avoidRepetitionRule}\n\n" .
            "Panduan Pelayanan:\n" .
            "1. Jawab informasi hotel dengan tepat: Kamar Deluxe & Executive Club Suite, sarapan di The Verandah Restaurant (06.30 - 10.30 WIB), Outdoor Infinity Pool Lt. 3, Aegora Spa Lt. 5, check-in 14.00 & check-out 12.00, Wi-Fi gratis, dan shuttle bandara.\n" .
            "2. Multibahasa & Dialek Otomatis: Pahami dan jawablah dengan fasih jika tamu berbicara dalam dialek lokal (Jawa, Sunda, Bali) maupun bahasa turis mancanegara (Mandarin, Jepang, Korea, Arab, Inggris) tanpa meminta mereka mengganti menu bahasa.\n" .
            "3. Proactive Upselling: Jika tamu menyebut perayaan ulang tahun, bulan madu (honeymoon), atau perayaan spesial, tawarkan paket dekorasi romantis kamar atau penawaran upgrade ke kamar Executive Club Suite dengan harga istimewa.\n" .
            "4. Gunakan format Markdown yang rapi (bold dan bullet points).\n" .
            "5. Jangan melakukan check-in mandiri di chat, melainkan sediakan formulir booking untuk diisi tamu yang nantinya akan dikonfirmasi pembayarannya oleh pihak hotel via WhatsApp.";
    }

    /**
     * Call Netra Runtime endpoint with DeepSeek model.
     */
    protected function callNetraApi(string $message, array $history, string $locale = 'id', ?string $docContext = null): ?string
    {
        $messages = [];
        $isFollowUp = count($history) > 0;

        // 1. System Prompt
        $systemContent = $this->getLocalizedSystemPrompt($locale, $isFollowUp);
        if ($docContext) {
            $systemContent .= "\n\nKonteks Dokumen Tambahan yang Diunggah Tamu:\n" . Str::limit($docContext, 3000);
        }

        $messages[] = [
            'role' => 'system',
            'content' => $systemContent,
        ];

        // 2. Chat History
        foreach ($history as $item) {
            if (!empty($item['content'])) {
                $messages[] = [
                    'role' => ($item['role'] === 'assistant') ? 'assistant' : 'user',
                    'content' => (string) $item['content'],
                ];
            }
        }

        // 3. Current User Message
        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $endpoint = str_ends_with($this->baseUrl, '/chat/completions')
            ? $this->baseUrl
            : $this->baseUrl . '/chat/completions';

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'stream' => false,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post($endpoint, $payload);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }
            if (isset($data['choices'][0]['text'])) {
                return trim($data['choices'][0]['text']);
            }
            if (isset($data['content'])) {
                return trim($data['content']);
            }
        } else {
            Log::error('Netra API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return null;
    }

    /**
     * 1. Deteksi Emosi & Komplain Berat (Sentiment Analysis & Immediate Escalation)
     */
    protected function detectUrgentComplaint(string $message, ?string $sessionId, string $locale): ?array
    {
        $lower = mb_strtolower($message);
        $complaintKeywords = [
            'bocor', 'rusak', 'berisik', 'kotor', 'bau', 'kecewa', 'marah', 'buruk sekali', 
            'tidak dingin', 'ac mati', 'mati lampu', 'menjijikkan', 'air mati', 'parah banget',
            'leaking', 'broken', 'noisy', 'dirty', 'smelly', 'disappointed', 'terrible', 'unacceptable'
        ];

        $isComplaint = false;
        foreach ($complaintKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $isComplaint = true;
                break;
            }
        }

        if ($isComplaint) {
            // Ekstrak nomor kamar jika ada (misal: "kamar 304", "room 201", "304")
            preg_match('/(?:kamar|room)\s*([0-9]{2,4})/i', $message, $roomMatch);
            $roomNumber = $roomMatch[1] ?? 'Tamu Kamar (Belum Disebutkan)';

            $escalationCode = 'ESC-' . strtoupper(Str::random(5));
            UrgentEscalation::create([
                'escalation_code' => $escalationCode,
                'session_id' => $sessionId,
                'room_number' => $roomNumber,
                'issue_summary' => Str::limit($message, 255),
                'sentiment' => 'angry_urgent',
                'priority' => 'critical',
                'status' => 'open',
            ]);

            if ($locale === 'en') {
                $content = "⚠️ **Immediate Escalation Notice & Sincere Apology**\n\n"
                    . "I am deeply sorry to hear about the inconvenience you are experiencing. This is certainly not the standard of service we strive to deliver at Aegora.\n\n"
                    . "🚨 **Action Taken:**\n"
                    . "• **Ticket Code:** `{$escalationCode}` (High Priority)\n"
                    . "• **Location:** Room **{$roomNumber}**\n"
                    . "• **Status:** Dispatched directly to our **Duty Manager & Front Office Team**.\n\n"
                    . "Our senior management team has received the summary of your concern and is dispatching engineering/staff to your room right away. You do not need to repeat your story. If you need to speak immediately, please lift your telephone and press **0**.";
            } else {
                $content = "⚠️ **Permohonan Maaf & Eskalasi Darurat Tim Manajemen**\n\n"
                    . "Kami sangat memohon maaf atas ketidaknyamanan yang Bapak/Ibu alami. Situasi ini sama sekali tidak mencerminkan standar keramahan dan kenyamanan yang kami janjikan di Aegora.\n\n"
                    . "🚨 **Tindakan Langsung:**\n"
                    . "• **Kode Tiket Eskalasi:** `{$escalationCode}` (Prioritas Kritis)\n"
                    . "• **Lokasi:** Kamar **{$roomNumber}**\n"
                    . "• **Status:** Diteruskan otomatis ke layar **Duty Manager & Tim Front Office**.\n\n"
                    . "Pihak pimpinan operasional kami telah menerima ringkasan kendala Anda dan tim teknisi/supervisor sedang meluncur ke kamar Anda sekarang agar masalah segera tuntas tanpa Anda harus mengulang cerita. Anda juga dapat menekan **ext. 0** jika ingin berbicara langsung saat ini.";
            }

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => [
                    'type' => 'urgent_escalation',
                    'code' => $escalationCode,
                    'room' => $roomNumber,
                ],
                'source' => 'system_escalation'
            ];
        }

        return null;
    }

    /**
     * 2. Deteksi Permintaan Fasilitas Instan (Housekeeping Ticket)
     */
    protected function detectHousekeepingRequest(string $message, ?string $sessionId, string $locale): ?array
    {
        $lower = mb_strtolower($message);
        $isHkRequest = (str_contains($lower, 'minta') || str_contains($lower, 'tambahan') || str_contains($lower, 'tolong') || str_contains($lower, 'need') || str_contains($lower, 'extra'))
            && (str_contains($lower, 'handuk') || str_contains($lower, 'sikat gigi') || str_contains($lower, 'sabun') || str_contains($lower, 'shampoo') || str_contains($lower, 'bantal') || str_contains($lower, 'selimut') || str_contains($lower, 'towel') || str_contains($lower, 'pillow') || str_contains($lower, 'amenities') || str_contains($lower, 'water') || str_contains($lower, 'air mineral'));

        if ($isHkRequest) {
            preg_match('/(?:kamar|room)\s*([0-9]{2,4})/i', $message, $roomMatch);
            $roomNumber = $roomMatch[1] ?? 'Kamar Tamu';

            $ticketCode = 'HK-' . strtoupper(Str::random(5));
            HousekeepingTicket::create([
                'ticket_code' => $ticketCode,
                'session_id' => $sessionId,
                'room_number' => $roomNumber,
                'items' => $message,
                'status' => 'pending',
                'notes' => 'Permintaan otomatis dari chat AI Concierge',
            ]);

            if ($locale === 'en') {
                $content = "🧹 **Housekeeping Request Dispatched!**\n\n"
                    . "Your request has been instantly queued into our Housekeeping Department System:\n\n"
                    . "• **Ticket ID:** `{$ticketCode}`\n"
                    . "• **Target Destination:** Room **{$roomNumber}**\n"
                    . "• **Estimated Delivery:** **10 – 15 minutes**\n\n"
                    . "Our room attendant is preparing the requested amenities and will bring them directly to your door. Have a wonderful rest!";
            } else {
                $content = "🧹 **Permintaan Housekeeping Berhasil Diteruskan!**\n\n"
                    . "Permintaan fasilitas kamar Anda telah otomatis masuk ke sistem tim Housekeeping kami:\n\n"
                    . "• **Nomor Tiket:** `{$ticketCode}`\n"
                    . "• **Tujuan:** Kamar **{$roomNumber}**\n"
                    . "• **Estimasi Pengantaran:** **10 – 15 menit**\n\n"
                    . "Petugas *room attendant* kami sedang menyiapkan barang yang Anda butuhkan dan akan segera mengantarkannya ke kamar Anda. Selamat beristirahat! 🌿";
            }

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => [
                    'type' => 'housekeeping_ticket',
                    'code' => $ticketCode,
                    'room' => $roomNumber,
                ],
                'source' => 'system_housekeeping'
            ];
        }

        return null;
    }

    /**
     * 3. Deteksi Pemesanan F&B dan Spa Terintegrasi (Folio Billing)
     */
    protected function detectRoomOrderRequest(string $message, ?string $sessionId, string $locale): ?array
    {
        $lower = mb_strtolower($message);
        $isFnbOrder = (str_contains($lower, 'pesan') || str_contains($lower, 'order') || str_contains($lower, 'reservasi'))
            && (str_contains($lower, 'nasi goreng') || str_contains($lower, 'makan') || str_contains($lower, 'minum') || str_contains($lower, 'kopi') || str_contains($lower, 'jus') || str_contains($lower, 'teh') || str_contains($lower, 'spa') || str_contains($lower, 'massage') || str_contains($lower, 'pijat'));

        if ($isFnbOrder && (str_contains($lower, 'kamar') || str_contains($lower, 'room') || str_contains($lower, 'jam') || str_contains($lower, 'pukul'))) {
            preg_match('/(?:kamar|room)\s*([0-9]{2,4})/i', $message, $roomMatch);
            $roomNumber = $roomMatch[1] ?? 'Kamar Tamu';
            $isSpa = (str_contains($lower, 'spa') || str_contains($lower, 'massage') || str_contains($lower, 'pijat'));

            $orderCode = ($isSpa ? 'SPA-' : 'FNB-') . strtoupper(Str::random(5));
            $estimatedPrice = $isSpa ? 480000 : 125000;

            RoomOrder::create([
                'order_code' => $orderCode,
                'session_id' => $sessionId,
                'room_number' => $roomNumber,
                'type' => $isSpa ? 'spa' : 'fnb',
                'items_json' => ['request_text' => $message],
                'total_amount' => $estimatedPrice,
                'billed_to_folio' => true,
                'status' => 'confirmed',
            ]);

            $formattedPrice = 'IDR ' . number_format($estimatedPrice, 0, ',', '.');

            if ($locale === 'en') {
                $content = ($isSpa ? "💆 **Spa Appointment Confirmed!**" : "🍽️ **In-Room Dining Order Received!**") . "\n\n"
                    . "Your order has been recorded into our hotel management system:\n\n"
                    . "• **Order Code:** `{$orderCode}`\n"
                    . "• **Room Folio:** Room **{$roomNumber}**\n"
                    . "• **Estimated Charge:** **{$formattedPrice}++**\n"
                    . "• **Billing Method:** **Directly charged to your Room Folio** (Pay upon check-out)\n\n"
                    . ($isSpa ? "Our wellness therapist is reserving your session slot." : "Our culinary kitchen team at The Verandah is preparing your fresh meal right away.");
            } else {
                $content = ($isSpa ? "💆 **Reservasi Spa Berhasil Dicatat!**" : "🍽️ **Pesanan Restoran Kamar Diterima!**") . "\n\n"
                    . "Pesanan Anda telah resmi tercatat di sistem kami:\n\n"
                    . "• **Kode Transaksi:** `{$orderCode}`\n"
                    . "• **Tujuan:** Kamar **{$roomNumber}**\n"
                    . "• **Estimasi Biaya:** **{$formattedPrice}++**\n"
                    . "• **Metode Tagihan:** **Otomatis Masuk ke Folio Tagihan Kamar** (Selesai saat Check-out)\n\n"
                    . ($isSpa ? "Terapis di Aegora Spa (Lantai 5) siap menyambut kehadiran relaksasi Anda." : "Tim kitchen The Verandah Restaurant sedang memasak hidangan segar dan akan segera mengantarkannya ke kamar Anda.");
            }

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => [
                    'type' => 'room_order',
                    'code' => $orderCode,
                    'room' => $roomNumber,
                ],
                'source' => 'system_order'
            ];
        }

        return null;
    }

    /**
     * 4. Deteksi Niat Pemesanan Kamar (Interactive Booking Form Intent)
     */
    protected function detectBookingIntent(string $message, ?string $sessionId, string $locale): ?array
    {
        $lower = mb_strtolower($message);
        $isBooking = str_contains($lower, 'pesan kamar') || str_contains($lower, 'booking kamar') || str_contains($lower, 'reservasi kamar') || str_contains($lower, 'book a room') || str_contains($lower, 'mau booking') || str_contains($lower, 'mau pesan');

        if ($isBooking) {
            if ($locale === 'en') {
                $content = "🛎️ **Hotel Room Reservation Request**\n\n"
                    . "We are delighted to assist with your stay! To ensure an effortless booking experience, please complete our **Reservation Form** below.\n\n"
                    . "Once submitted, your booking voucher is generated immediately, and our Hotel Reservations Team will contact you via WhatsApp for payment and special arrangement verification.";
            } else {
                $content = "🛎️ **Formulir Pemesanan Kamar Aegora Hotel**\n\n"
                    . "Dengan senang hati kami siap melayani reservasi menginap Anda! Silakan isi **Formulir Reservasi Singkat** di bawah ini.\n\n"
                    . "Setelah diisi, voucher rincian booking Anda akan langsung terbit, dan Tim Reservasi Resmi kami akan langsung menghubungi nomor WhatsApp Anda untuk konfirmasi pembayaran dan kebutuhan khusus lainnya.";
            }

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => [
                    'type' => 'show_booking_form',
                ],
                'source' => 'system_booking_form'
            ];
        }

        return null;
    }

    /**
     * Get extracted text from uploaded PDF document in session.
     */
    protected function getDocumentContext(?string $sessionId): ?string
    {
        if (!$sessionId) return null;
        $doc = ChatDocument::where('session_id', $sessionId)->latest()->first();
        return $doc ? $doc->extracted_text : null;
    }

    /**
     * Fallback cerdas bebas pengulangan jika API offline.
     */
    protected function generateSimulatedHotelResponse(string $message, string $locale = 'id', array $history = []): array
    {
        $lower = mb_strtolower(trim($message));
        $isFollowUp = count($history) > 0;
        $facility = null;

        // Proactive Upselling jika tamu menyebut ulang tahun / honeymoon
        if (str_contains($lower, 'ulang tahun') || str_contains($lower, 'birthday') || str_contains($lower, 'honeymoon') || str_contains($lower, 'bulan madu') || str_contains($lower, 'anniversary') || str_contains($lower, 'merayakan')) {
            $content = ($locale === 'en')
                ? "🎉 **Special Celebration detected! Happy Anniversary / Birthday!** 🥂\n\n"
                . "To make your celebration unforgettable, we have an exclusive offer:\n"
                . "• **Romantic Room Setup & Artisanal Cake:** Only IDR 350,000++ (Includes fresh rose petals and custom message).\n"
                . "• **Special Suite Upgrade:** Enjoy 30% OFF to upgrade to our **Executive Club Suite** with complimentary Evening Cocktails on Level 21.\n\n"
                . "Would you like me to reserve this special decoration setup for your stay?"
                : "🎉 **Perayaan Spesial Terdeteksi! Selamat Ulang Tahun / Bulan Madu!** 🥂\n\n"
                . "Agar momen istimewa Anda di Aegora semakin berkesan, kami menyediakan penawaran eksklusif:\n"
                . "• **Paket Dekorasi Romantis & Cake Artisanal:** Hanya IDR 350.000++ (Sudah termasuk taburan mawar segar, lilin aromatik, dan kue cokelat artisanal).\n"
                . "• **Penawaran Upgrade Spesial:** Diskon khusus 30% untuk upgrade ke **Executive Club Suite** lengkap dengan akses Club Lounge di Lantai 21.\n\n"
                . "Apakah Anda ingin saya catatkan paket dekorasi ini untuk melengkapi masa menginap Anda?";

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => null,
                'source' => 'simulated_upselling'
            ];
        }

        // Bahasa Inggris
        if ($locale === 'en') {
            if (str_contains($lower, 'pool') || str_contains($lower, 'breakfast') || str_contains($lower, 'sarapan') || str_contains($lower, 'makan')) {
                $content = ($isFollowUp ? "" : "Welcome to Aegora Hotel 🌿\n\n")
                    . "Breakfast is served daily at **The Verandah Restaurant** from **06:30 AM – 10:30 AM WIB**, featuring an artisanal buffet of authentic Indonesian delicacies and international favorites.\n\n"
                    . "Our **Outdoor Infinity Pool & Bar on Level 3** is open from 07:00 AM – 10:00 PM WIB with complimentary fresh towels.";
                
                $facility = [
                    'title' => 'Outdoor Infinity Pool & Bar',
                    'location' => 'Level 3 South Deck',
                    'hours' => '07:00 AM – 10:00 PM WIB',
                    'image' => asset('images/infinity-pool.jpg'),
                    'tags' => ['Complimentary Fresh Towels', 'Kids Splash Zone', 'Poolside Mocktail Bar'],
                    'actions' => [
                        ['label' => 'View Dining Menu', 'primary' => true, 'action' => 'menu'],
                        ['label' => 'Reserve Breakfast Table', 'primary' => false, 'action' => 'breakfast'],
                        ['label' => 'Contact Reception', 'primary' => false, 'action' => 'reception']
                    ]
                ];
            } elseif (str_contains($lower, 'room') || str_contains($lower, 'suite') || str_contains($lower, 'deluxe') || str_contains($lower, 'rate') || str_contains($lower, 'tarif')) {
                $content = "Here are our signature accommodations:\n\n"
                    . "1. **Deluxe King / Twin (36 m²)**\n"
                    . "   • Rates start from **IDR 1,250,000++ / night**\n"
                    . "   • King Koil plush bedding, walk-in rain shower, 55\" Smart TV, free high-speed Wi-Fi.\n\n"
                    . "2. **Executive Club Suite (58 m²)**\n"
                    . "   • Rates start from **IDR 2,450,000++ / night**\n"
                    . "   • Includes Level 21 Executive Club Lounge access (Free evening cocktails & canapés), marble bathtub, and Nespresso.\n\n"
                    . "Type 'book a room' whenever you wish to reserve!";
            } else {
                $content = ($isFollowUp ? "" : "Thank you for reaching out to Aegora Assist 🌟\n\n")
                    . "I can promptly assist you with:\n"
                    . "• Room information & reservation forms\n"
                    . "• Instant housekeeping delivery (e.g. 'Send towels to room 304')\n"
                    . "• In-room dining & spa reservations charged to your room folio\n"
                    . "• Wi-Fi details, check-in & express check-out guides\n\n"
                    . "How may I assist you right now?";
            }

            return ['content' => $content, 'facility' => $facility, 'special_action' => null, 'source' => 'simulated'];
        }

        // Bahasa Indonesia (Default)
        if (str_contains($lower, 'kolam') || str_contains($lower, 'pool') || str_contains($lower, 'sarapan') || str_contains($lower, 'makan')) {
            $content = ($isFollowUp ? "" : "Halo! Selamat datang di Aegora Hotel 🌿\n\n")
                . "Sarapan pagi di **The Verandah Restaurant** tersedia setiap hari pukul **06.30 – 10.30 WIB** dengan konsep prasmanan masakan Nusantara & artisanal internasional.\n\n"
                . "Untuk rekreasi air, **Outdoor Infinity Pool & Bar di Lantai 3** buka setiap hari pukul 07.00 – 22.00 WIB dengan panorama kota spektakuler.";
            
            $facility = [
                'title' => 'Outdoor Infinity Pool & Bar',
                'location' => 'Lantai 3 Deck Selatan',
                'hours' => '07.00 – 22.00 WIB',
                'image' => asset('images/infinity-pool.jpg'),
                'tags' => ['Handuk Bersih Gratis', 'Kids Splash Area', 'Poolside Mocktail Bar'],
                'actions' => [
                    ['label' => 'Lihat Menu Restoran', 'primary' => true, 'action' => 'menu'],
                    ['label' => 'Pesan Meja Sarapan', 'primary' => false, 'action' => 'breakfast'],
                    ['label' => 'Hubungi Resepsionis', 'primary' => false, 'action' => 'reception']
                ]
            ];
        } elseif (str_contains($lower, 'kamar') || str_contains($lower, 'deluxe') || str_contains($lower, 'executive') || str_contains($lower, 'tarif')) {
            $content = "Pilihan kamar terbaik kami untuk masa menginap Anda:\n\n"
                . "1. **Deluxe King / Twin (36 m²)** — Mulai **IDR 1.250.000++ / malam**\n"
                . "   • King Koil bed, rain shower, Smart TV 55\", Wi-Fi 100 Mbps.\n\n"
                . "2. **Executive Club Suite (58 m²)** — Mulai **IDR 2.450.000++ / malam**\n"
                . "   • Akses Executive Lounge Lt. 21 (Free Evening Cocktails), bathtub marmer, Nespresso pribadi.\n\n"
                . "Ketik 'mau pesan kamar' jika ingin mengisi formulir reservasi langsung!";
        } else {
            $content = ($isFollowUp ? "" : "Terima kasih telah menghubungi Customer Service Aegora Assist 🌟\n\n")
                . "Saya siap membantu Anda dengan layanan cerdas:\n"
                . "• **Pemesanan Kamar** via formulir interaktif\n"
                . "• **Permintaan Housekeeping Instan** (Contoh: *'Minta 2 handuk ke kamar 304'*)\n"
                . "• **Pemesanan Makanan & Spa** otomatis masuk ke tagihan kamar\n"
                . "• **Informasi Fasilitas & Jadwal** (Sarapan, Kolam Renang, Wi-Fi)\n\n"
                . "Ada yang bisa saya bantu sekarang?";
        }

        return [
            'content' => $content,
            'facility' => $facility,
            'special_action' => null,
            'source' => 'simulated'
        ];
    }

    /**
     * Inspect text to detect if any facility card should accompany the response.
     */
    protected function detectFacilityAttachment(string $query, string $response, string $locale = 'id'): ?array
    {
        $text = mb_strtolower($query . ' ' . $response);
        if (str_contains($text, 'infinity pool') || str_contains($text, 'kolam renang') || str_contains($text, 'verandah')) {
            return [
                'title' => 'Outdoor Infinity Pool & Bar',
                'location' => $locale === 'en' ? 'Level 3 South Deck' : 'Lantai 3 Deck Selatan',
                'hours' => $locale === 'en' ? '07:00 AM – 10:00 PM WIB' : '07.00 – 22.00 WIB',
                'image' => asset('images/infinity-pool.jpg'),
                'tags' => $locale === 'en' 
                    ? ['Complimentary Fresh Towels', 'Kids Splash Zone', 'Poolside Mocktail Bar']
                    : ['Handuk Bersih Gratis', 'Kids Splash Area', 'Poolside Mocktail Bar'],
                'actions' => [
                    ['label' => $locale === 'en' ? 'View Dining Menu' : 'Lihat Menu Restoran', 'primary' => true, 'action' => 'menu'],
                    ['label' => $locale === 'en' ? 'Reserve Breakfast' : 'Pesan Meja Sarapan', 'primary' => false, 'action' => 'breakfast'],
                    ['label' => $locale === 'en' ? 'Contact Staff' : 'Hubungi Resepsionis', 'primary' => false, 'action' => 'reception']
                ]
            ];
        }
        return null;
    }
}
