<?php

namespace App\Services;

use App\Models\ChatDocument;
use App\Models\GuestBooking;
use App\Models\HotelKnowledge;
use App\Models\HotelSetting;
use App\Models\HousekeepingTicket;
use App\Models\Room;
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
     * Generate response with Intelligent Dispatching, DB Knowledge Base & Function Calling.
     *
     * @param string $message
     * @param array $history
     * @param string $locale ('id', 'en', etc.)
     * @param string|null $sessionId
     * @param bool $voiceMode
     * @return array{content: string, facility: array|null, special_action: array|null, source: string}
     */
    public function generateResponse(string $message, array $history = [], string $locale = 'id', ?string $sessionId = null, bool $voiceMode = false): array
    {
        // 0. Deteksi Situasi Darurat (PRD Bagian 7: Medis, Keamanan, Kebakaran)
        $emergencyAction = $this->detectEmergencySituation($message, $sessionId, $locale);
        if ($emergencyAction) {
            return $emergencyAction;
        }

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

        // 4. PRD Modul 4: Deteksi Cari Reservasi, Cek Tagihan (dengan Verifikasi Identitas) & Keberatan Tagihan
        $checkInOutAction = $this->detectReservationOrBillingRequest($message, $history, $sessionId, $locale);
        if ($checkInOutAction) {
            return $checkInOutAction;
        }

        // 5. Deteksi Permintaan Booking Kamar (Tampilkan Interactive Booking Form)
        $bookingAction = $this->detectBookingIntent($message, $sessionId, $locale);
        if ($bookingAction) {
            return $bookingAction;
        }

        // 6. Cek Dokumen PDF Konteks RAG (jika ada file terunggah pada session ini)
        $documentContext = $this->getDocumentContext($sessionId);

        // 7. Hubungi Netra Runtime (Model DeepSeek) jika API Key tersedia
        if (!empty($this->apiKey) && $this->apiKey !== 'YOUR_NETRA_API_KEY_HERE') {
            try {
                $response = $this->callNetraApi($message, $history, $locale, $documentContext, $voiceMode);
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

        // 8. Fallback Cerdas Berbasis Database Resmi Hotel & Non-repetitif
        $simulated = $this->generateSimulatedHotelResponse($message, $locale, $history, $voiceMode);
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_NETRA_API_KEY_HERE') {
            $simulated['source'] = 'simulated_no_key';
        }

        return $simulated;
    }

    /**
     * Build live hotel knowledge base & room data directly from MySQL database (PRD Modul 2 & 3).
     */
    protected function buildDatabaseKnowledgeContext(string $locale = 'id'): string
    {
        $lines = [];

        try {
            $rooms = Room::where('is_available', true)->orderBy('price_per_night')->get();
            if ($rooms->isNotEmpty()) {
                $lines[] = '=== DAFTAR KAMAR RESMI AEGORA HOTEL (DARI DATABASE) ===';
                foreach ($rooms as $room) {
                    $price = 'IDR ' . number_format((float) $room->price_per_night, 0, ',', '.');
                    $facList = is_array($room->facilities) ? implode(', ', $room->facilities) : '';
                    $lines[] = "- {$room->name} ({$room->size_sqm} m², maks {$room->capacity} tamu, {$room->bed_type}): {$price}/malam (+{$room->tax_percent}% pajak & layanan). Fasilitas: {$facList}. {$room->description}";
                }
            }

            $knowledge = HotelKnowledge::where('is_active', true)->orderBy('sort_order')->get();
            if ($knowledge->isNotEmpty()) {
                $lines[] = '';
                $lines[] = '=== INFORMASI, FASILITAS, JAM OPERASIONAL & KEBIJAKAN RESMI HOTEL ===';
                foreach ($knowledge as $item) {
                    $meta = array_filter([
                        $item->location ? "Lokasi: {$item->location}" : null,
                        $item->operating_hours ? "Jam: {$item->operating_hours}" : null,
                        $item->extra_fee ? "Biaya: {$item->extra_fee}" : null,
                    ]);
                    $metaStr = !empty($meta) ? ' [' . implode(' | ', $meta) . ']' : '';
                    $lines[] = "- [{$item->category}] {$item->title}{$metaStr}: " . $item->getLocalizedContent($locale);
                }
            }
        } catch (Throwable) {
            // Safe fallback if table not migrated yet
        }

        return implode("\n", $lines);
    }

    /**
     * Get system prompt customized by Persona, Language, DB Knowledge Base, and Voice Mode.
     */
    protected function getLocalizedSystemPrompt(string $locale, bool $isFollowUp = false, bool $voiceMode = false): string
    {
        $persona = HotelSetting::getVal('ai_persona', 'hospitable_warm');
        $hotelName = HotelSetting::getVal('hotel_name', 'Aegora Hotel & Hospitality');

        $styleInstruction = match ($persona) {
            'bali_resort' => "Gaya bahasa: Santai, hangat, akrab, ramah pulau Bali ('Halo Kak!', 'Dengan senang hati ya Kak...'). Ramah namun tetap menjaga kesopanan staf hotel.",
            'luxury_heritage' => "Gaya bahasa: Anggun, berkelas, penuh tata krama nusantara, bertutur kata sangat santun, hangat, dan berwibawa.",
            'jakarta_business' => "Gaya bahasa: Modern, tenang, efisien, sopan, dan berwibawa layaknya front office hotel bintang lima profesional — tetap hangat dan tidak kaku.",
            default => "Gaya bahasa: Seperti admin resepsionis hotel bintang lima yang baik, sangat ramah, hangat, sopan, dan berwibawa. Gunakan bahasa percakapan alami yang luwes — JANGAN terlalu baku, kaku, atau terasa seperti robot/template. Tetap elegan dan meyakinkan.",
        };

        $avoidRepetitionRule = $isFollowUp
            ? "PENTING: Percakapan sedang berlangsung! JANGAN mengulang salam perkenalan formal ('Halo, saya Aegora Assist...') lagi. Langsung jawab ke inti pertanyaan tamu dengan ramah dan alami."
            : "Perkenalkan diri secara ringkas dan hangat sebagai Aegora Assist, asisten virtual {$hotelName}, lalu langsung bantu kebutuhan tamu.";

        $voiceRule = $voiceMode
            ? "\nMODE SUARA AKTIF (VOICE CALL): Jawaban Anda akan dibacakan langsung melalui suara (Deepgram TTS). Gunakan kalimat lisan yang mengalir alami, ramah, sopan, dan berwibawa layaknya petugas resepsionis sedang berbicara di telepon (maksimal 2–4 kalimat singkat dan jelas). JANGAN gunakan markdown (**bold**, bullet points, simbol emoji, atau kode teknis) agar terdengar sangat natural saat diucapkan."
            : '';

        $dbKnowledge = $this->buildDatabaseKnowledgeContext($locale);

        if ($locale === 'en') {
            return "You are 'Aegora Assist', the virtual front-office concierge of {$hotelName} (PT Aegora).\n" .
                "Persona & Tone: Warm, gracious, polite, naturally conversational, and poised with quiet authority like an experienced 5-star hotel front-desk specialist — never stiff or robotic. ({$styleInstruction})\n" .
                "{$avoidRepetitionRule}{$voiceRule}\n\n" .
                "Strict PRD Rules:\n" .
                "1. Always mirror the exact language the guest is currently speaking or typing (English, Indonesian, Japanese, French, German, Spanish, Mandarin, Korean, Arabic, or casual slang) without asking them to switch menus.\n" .
                "2. Ground EVERY answer strictly in the official hotel database below. Never invent prices, availability, or policies. If something is unavailable in the data, politely acknowledge it and offer to connect with our Front Desk staff.\n" .
                "3. Never promise unauthorized discounts, compensation, or policy exceptions. Never ask for or process credit card numbers in chat. Never disclose another guest's reservation or personal data.\n" .
                "4. For reservation lookup or billing inquiries, always require identity verification first (booking code + last name, or full name + check-in date + phone/email).\n" .
                "5. Politely steer off-topic questions back to Aegora Hotel hospitality services. Proactively offer romantic room decoration or Suite upgrades when guests mention birthdays, honeymoons, or anniversaries.\n\n" .
                $dbKnowledge;
        }

        return "Anda adalah 'Aegora Assist', asisten virtual Front Office & AI Concierge resmi {$hotelName} (PT Aegora).\n" .
            "Karakter & Nada Bicara: {$styleInstruction}\n" .
            "{$avoidRepetitionRule}{$voiceRule}\n\n" .
            "Aturan Resmi PRD Aegora Hotel:\n" .
            "1. Adaptasi Bahasa Otomatis: Pahami bahasa sehari-hari, singkatan, bahasa gaul, dan campuran Indo-Inggris (misal 'ada kmr kosong ga weekend ini?', 'sarapan jam brp?'). Selalu balas dalam BAHASA YANG SAMA dengan bahasa yang sedang digunakan tamu (Indonesia, Inggris, Jepang, Mandarin, Korea, Arab, Prancis, Jerman, Spanyol, maupun dialek lokal).\n" .
            "2. Akurasi Berbasis Data Resmi: Jawab HANYA berdasarkan data resmi hotel di bawah ini. Jangan mengarang harga, fasilitas, atau kebijakan. Jika informasi tidak tersedia, akui dengan sopan lalu tawarkan bantuan staf resepsionis (ext. 0 / WhatsApp +62 812-3456-7890).\n" .
            "3. Guardrail & Privasi: Jangan menjanjikan diskon, kompensasi, atau pengecualian kebijakan tanpa persetujuan staf. Jangan meminta atau memproses nomor kartu kredit di chat. Jangan pernah membagikan data tamu lain. Jika pertanyaan di luar topik hotel, alihkan dengan sopan.\n" .
            "4. Verifikasi Check-in & Tagihan: Sebelum menampilkan detail reservasi atau tagihan, wajib minta verifikasi (kode booking + nama belakang, atau nama lengkap + tanggal check-in + nomor HP/email).\n" .
            "5. Proactive Upselling: Jika tamu menyebut ulang tahun, honeymoon, atau anniversary, tawarkan paket dekorasi romantis atau upgrade Executive Club Suite dengan hangat.\n\n" .
            $dbKnowledge;
    }

    /**
     * Call Netra Runtime endpoint with DeepSeek model.
     */
    protected function callNetraApi(string $message, array $history, string $locale = 'id', ?string $docContext = null, bool $voiceMode = false): ?string
    {
        $messages = [];
        $isFollowUp = count($history) > 0;

        // 1. System Prompt
        $systemContent = $this->getLocalizedSystemPrompt($locale, $isFollowUp, $voiceMode);
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
     * 0. Deteksi Situasi Darurat (PRD Bagian 7: Medis, Keamanan, Kebakaran)
     */
    protected function detectEmergencySituation(string $message, ?string $sessionId, string $locale): ?array
    {
        $lower = mb_strtolower($message);
        $emergencyKeywords = [
            'kebakaran', 'api besar', 'asap tebal', 'pingsan', 'darurat medis', 'ambulans', 'kecelakaan',
            'maling', 'pencurian', 'bahaya', 'emergency', 'fire alarm', 'medical emergency', 'ambulance', 'security threat',
        ];

        foreach ($emergencyKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                preg_match('/(?:kamar|room)\s*([0-9]{2,4})/i', $message, $roomMatch);
                $roomNumber = $roomMatch[1] ?? 'Area Hotel';
                $code = 'EMG-' . strtoupper(Str::random(5));

                UrgentEscalation::create([
                    'escalation_code' => $code,
                    'session_id' => $sessionId,
                    'room_number' => $roomNumber,
                    'issue_summary' => '[DARURAT] ' . Str::limit($message, 240),
                    'sentiment' => 'emergency',
                    'priority' => 'critical',
                    'status' => 'open',
                ]);

                $content = $locale === 'en'
                    ? "🚨 **URGENT EMERGENCY ALERT (`{$code}`)**\n\nPlease stay calm and prioritize your safety. I have immediately alerted our **24/7 Duty Manager, Security & Medical Response Team**.\n\n• **Hotel Emergency Line (In-Room Phone):** Dial **9** or **0** immediately\n• **24/7 Direct Line:** **+62 21 5099 8800** / WhatsApp **+62 812-3456-7890**\n• **National Emergency:** **112**\n\nOur response team is on the way right now."
                    : "🚨 **PERINGATAN DARURAT PRIORITAS TINGGI (`{$code}`)**\n\nMohon tetap tenang dan utamakan keselamatan Anda. Saya telah segera mengirimkan notifikasi darurat ke **Duty Manager, Tim Keamanan & Klinik 24 Jam** Aegora Hotel.\n\n• **Saluran Darurat Telepon Kamar:** Tekan **9** atau **0** sekarang juga\n• **Telepon Hotel 24 Jam:** **(021) 5099-8800** / WhatsApp **+62 812-3456-7890**\n• **Layanan Darurat Nasional:** **112**\n\nTim kami sedang bergerak menuju lokasi Anda segera.";

                return [
                    'content' => $content,
                    'facility' => null,
                    'special_action' => ['type' => 'urgent_escalation', 'code' => $code, 'room' => $roomNumber],
                    'source' => 'system_emergency',
                ];
            }
        }

        return null;
    }

    /**
     * 4. PRD Modul 4: Check-in / Check-out Assistant, Pencarian Reservasi & Rincian Tagihan dengan Verifikasi Identitas
     */
    protected function detectReservationOrBillingRequest(string $message, array $history, ?string $sessionId, string $locale): ?array
    {
        $lower = mb_strtolower($message);

        // Guardrail PRD 6.5: Keberatan tagihan -> tidak mengubah biaya, langsung eskalasi ke staf
        $isBillingDispute = (str_contains($lower, 'tagihan') || str_contains($lower, 'bill') || str_contains($lower, 'invoice') || str_contains($lower, 'folio') || str_contains($lower, 'charge'))
            && (str_contains($lower, 'salah') || str_contains($lower, 'keberatan') || str_contains($lower, 'keliru') || str_contains($lower, 'double') || str_contains($lower, 'wrong') || str_contains($lower, 'dispute') || str_contains($lower, 'overcharged') || str_contains($lower, 'hapus biaya'));

        if ($isBillingDispute) {
            $code = 'BIL-' . strtoupper(Str::random(5));
            UrgentEscalation::create([
                'escalation_code' => $code,
                'session_id' => $sessionId,
                'room_number' => 'Front Desk Billing',
                'issue_summary' => '[Keberatan Tagihan] ' . Str::limit($message, 230),
                'sentiment' => 'billing_dispute',
                'priority' => 'high',
                'status' => 'open',
            ]);

            $content = $locale === 'en'
                ? "I completely understand your concern regarding the bill. Because billing adjustments require direct verification by our Front Office team, I have escalated your inquiry to our **Duty Manager** under reference **`{$code}`**.\n\nOur Front Desk specialist will review your folio carefully and contact you right away. You may also dial **ext. 0** or WhatsApp **+62 812-3456-7890** if you wish to speak immediately."
                : "Baik Bapak/Ibu, saya sangat memahami kekhawatiran Anda terkait rincian tagihan tersebut. Untuk peninjauan atau penyesuaian biaya, hal ini ditangani langsung oleh tim Front Office kami agar akurat dan transparan.\n\nSaya sudah meneruskan laporan ini ke **Duty Manager** dengan nomor tiket **`{$code}`**. Staf kami akan segera meninjau folio Anda dan menghubungi Anda kembali, atau Bapak/Ibu bisa langsung menekan **ext. 0** di telepon kamar.";

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => ['type' => 'urgent_escalation', 'code' => $code, 'room' => 'Billing'],
                'source' => 'system_billing_escalation',
            ];
        }

        // Cek apakah ada kode booking (AEG-BKG-XXXXXX) atau niat mencari reservasi / cek tagihan
        $hasBookingCode = (bool) preg_match('/\b(AEG-BKG-[A-Z0-9]{4,10})\b/i', $message, $codeMatch);
        $isLookupIntent = $hasBookingCode
            || str_contains($lower, 'cari booking')
            || str_contains($lower, 'cari reservasi')
            || str_contains($lower, 'cek booking')
            || str_contains($lower, 'cek reservasi')
            || str_contains($lower, 'tagihan saya')
            || str_contains($lower, 'cek tagihan')
            || str_contains($lower, 'rincian tagihan')
            || str_contains($lower, 'berapa tagihan')
            || str_contains($lower, 'my booking')
            || str_contains($lower, 'my reservation')
            || str_contains($lower, 'check reservation')
            || str_contains($lower, 'my bill')
            || str_contains($lower, 'my folio');

        // Also check if previous assistant message asked for booking verification
        $askedVerificationRecently = false;
        if (!$isLookupIntent && !empty($history)) {
            $lastAssistant = collect($history)->where('role', 'assistant')->last();
            if ($lastAssistant && (str_contains(mb_strtolower($lastAssistant['content']), 'kode booking') || str_contains(mb_strtolower($lastAssistant['content']), 'booking code'))) {
                $askedVerificationRecently = true;
                $isLookupIntent = true;
            }
        }

        if (!$isLookupIntent) {
            return null;
        }

        // Gather combined context from current message + recent user message for verification pair
        $recentUserContext = $message;
        if ($askedVerificationRecently) {
            $prevUser = collect($history)->where('role', 'user')->last();
            if ($prevUser && $prevUser['content'] !== $message) {
                $recentUserContext = $prevUser['content'] . ' ' . $message;
            }
        }

        preg_match('/\b(AEG-BKG-[A-Z0-9]{4,10})\b/i', $recentUserContext, $resolvedCodeMatch);
        $bookingCode = isset($resolvedCodeMatch[1]) ? strtoupper($resolvedCodeMatch[1]) : null;

        // Method A (PRD 6.3): Verifikasi Kode Booking + Nama Belakang Tamu
        if ($bookingCode) {
            $booking = GuestBooking::with('room')->where('booking_code', $bookingCode)->first();

            // Check if guest also included a name beyond the booking code
            $textWithoutCode = trim(preg_replace('/\bAEG-BKG-[A-Z0-9]{4,10}\b/i', '', $recentUserContext));
            $cleanedWords = preg_replace('/[^a-zA-Z\s]/', ' ', $textWithoutCode);
            $stopWords = ['cek', 'cari', 'booking', 'reservasi', 'tagihan', 'saya', 'kode', 'nama', 'atas', 'berapa', 'tolong', 'lihat', 'detail', 'check', 'my', 'reservation', 'bill', 'folio', 'code', 'name', 'please', 'for', 'the', 'is', 'dan', 'and', 'bapak', 'ibu', 'bpk', 'pak', 'bu', 'mr', 'mrs', 'ms'];
            $candidateWords = array_values(array_filter(
                preg_split('/\s+/', mb_strtolower(trim($cleanedWords))) ?: [],
                fn ($w) => mb_strlen($w) >= 2 && !in_array($w, $stopWords, true)
            ));

            if (empty($candidateWords)) {
                $content = $locale === 'en'
                    ? "I'd be happy to check your reservation **`{$bookingCode}`** and billing details. For your privacy and security, could you please confirm the **last name** registered on this booking?"
                    : "Dengan senang hati saya bantu cek detail reservasi **`{$bookingCode}`** dan rincian tagihannya. Demi menjaga privasi data Bapak/Ibu, boleh informasikan **nama belakang** yang terdaftar pada reservasi tersebut?";

                return [
                    'content' => $content,
                    'facility' => null,
                    'special_action' => ['type' => 'verification_required', 'booking_code' => $bookingCode],
                    'source' => 'system_verification_prompt',
                ];
            }

            if ($booking) {
                $guestParts = preg_split('/\s+/', mb_strtolower(preg_replace('/[^a-zA-Z\s]/', ' ', $booking->guest_name)));
                $guestParts = array_values(array_filter($guestParts ?: [], fn ($w) => mb_strlen($w) >= 2 && !in_array($w, ['bpk', 'bapak', 'ibu', 'mr', 'mrs', 'ms'], true)));
                $lastName = end($guestParts) ?: '';

                $matched = false;
                foreach ($candidateWords as $word) {
                    if ($lastName !== '' && ($word === $lastName || str_contains($lastName, $word) || str_contains($word, $lastName))) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    return $this->formatVerifiedBookingResponse($booking, $locale);
                }
            }

            // Verification failed — PRD 6.3: Do NOT open any data, offer staff assistance
            $content = $locale === 'en'
                ? "I'm sorry, I couldn't verify a reservation matching that booking code and last name. For security reasons, I can only display details once the credentials match. Please double-check your booking code and name, or feel free to reach our Front Desk at **ext. 0** or WhatsApp **+62 812-3456-7890** so our team can assist you directly."
                : "Mohon maaf Bapak/Ibu, data kode booking dan nama belakang yang dimasukkan belum cocok di sistem kami. Demi keamanan privasi tamu, saya belum dapat menampilkan detail reservasi tersebut.\n\nSilakan periksa kembali kode booking serta ejaan nama Anda, atau hubungi tim resepsionis kami di **ext. 0** / WhatsApp **+62 812-3456-7890** agar staf kami bantu cek secara langsung ya.";

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => ['type' => 'verification_failed'],
                'source' => 'system_verification_failed',
            ];
        }

        // Method B (PRD 6.3): Verifikasi Nama Lengkap + Tanggal Check-in + No. HP / Email
        preg_match('/\b(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/', $recentUserContext, $dateMatch);
        preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $recentUserContext, $emailMatch);
        preg_match('/(\+?62|08)[0-9\-\s]{7,15}/', $recentUserContext, $phoneMatch);

        if (!empty($dateMatch[1]) && (!empty($emailMatch[1]) || !empty($phoneMatch[0]))) {
            try {
                $targetDate = \Carbon\Carbon::parse($dateMatch[1])->format('Y-m-d');
                $query = GuestBooking::with('room')->whereDate('check_in_date', $targetDate);

                if (!empty($emailMatch[1])) {
                    $query->where('guest_email', $emailMatch[1]);
                } else {
                    $digits = preg_replace('/[^0-9]/', '', $phoneMatch[0]);
                    $tail = substr($digits, -8);
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(guest_phone, '-', ''), ' ', ''), '+', '') LIKE ?", ['%' . $tail]);
                }

                $found = $query->first();
                if ($found) {
                    $guestLast = last(array_filter(explode(' ', mb_strtolower(preg_replace('/[^a-zA-Z\s]/', ' ', $found->guest_name)))));
                    if ($guestLast && str_contains(mb_strtolower($recentUserContext), $guestLast)) {
                        return $this->formatVerifiedBookingResponse($found, $locale);
                    }
                }
            } catch (Throwable) {
                // Fall through to verification failed
            }

            $content = $locale === 'en'
                ? "I couldn't find a verified booking matching that name, check-in date, and contact information. Please check the details again or contact our Front Desk at **ext. 0** so our team can assist you immediately."
                : "Mohon maaf, saya belum menemukan reservasi yang cocok dengan kombinasi nama, tanggal check-in, dan kontak tersebut. Silakan periksa kembali datanya atau hubungi Front Desk kami di **ext. 0** agar staf kami bantu langsung.";

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => ['type' => 'verification_failed'],
                'source' => 'system_verification_failed',
            ];
        }

        // Ask for identity verification (PRD 6.3)
        $content = $locale === 'en'
            ? "I'd be glad to help you check your reservation and billing summary! To protect your privacy, please share either:\n• Your **Booking Code** and **Last Name** (for example: `AEG-BKG-884210 Wijaya`), or\n• Your **Full Name**, **Check-in Date**, and **registered Phone/Email**."
            : "Tentu, dengan senang hati saya bantu cek detail reservasi dan rincian tagihan Bapak/Ibu! Demi keamanan data, mohon kirimkan salah satu informasi verifikasi berikut:\n• **Kode Booking** dan **Nama Belakang** (contoh: `AEG-BKG-884210 Wijaya`), atau\n• **Nama Lengkap**, **Tanggal Check-in**, dan **No. HP / Email** yang terdaftar.";

        return [
            'content' => $content,
            'facility' => null,
            'special_action' => ['type' => 'verification_required'],
            'source' => 'system_verification_prompt',
        ];
    }

    /**
     * Format verified GuestBooking & Folio Billing breakdown (PRD 6.3, 6.4, 6.5).
     */
    protected function formatVerifiedBookingResponse(GuestBooking $booking, string $locale): array
    {
        $bill = $booking->getBillingBreakdown();
        $fmt = fn ($val) => 'IDR ' . number_format((float) $val, 0, ',', '.');

        $paymentLabelsId = [
            'unpaid' => 'Belum Lunas (Menunggu Pembayaran)',
            'dp_paid' => 'DP Terbayar (Pelunasan saat Check-out)',
            'paid' => 'Lunas',
            'refunded' => 'Dana Dikembalikan',
        ];
        $paymentLabelsEn = [
            'unpaid' => 'Unpaid (Pending Settlement)',
            'dp_paid' => 'Deposit Paid (Balance upon Check-out)',
            'paid' => 'Paid in Full',
            'refunded' => 'Refunded',
        ];

        $payLabel = $locale === 'en'
            ? ($paymentLabelsEn[$bill['payment_status']] ?? strtoupper($bill['payment_status']))
            : ($paymentLabelsId[$bill['payment_status']] ?? strtoupper($bill['payment_status']));

        $roomNumText = $booking->room_number
            ? ($locale === 'en' ? "Room {$booking->room_number}" : "Kamar {$booking->room_number}")
            : ($locale === 'en' ? 'Assigned upon Check-in' : 'Ditentukan saat Check-in');

        $extraOrdersText = '';
        if ($bill['folio_orders']->isNotEmpty()) {
            foreach ($bill['folio_orders'] as $order) {
                $itemDesc = $order->items_json['request_text'] ?? strtoupper($order->type);
                $extraOrdersText .= "  - {$order->order_code} ({$itemDesc}): " . $fmt($order->total_amount) . "\n";
            }
        }

        if ($locale === 'en') {
            $content = "✅ **Verified Reservation & Folio Summary**\n\n"
                . "• **Booking Code:** `{$booking->booking_code}`\n"
                . "• **Guest Name:** {$booking->guest_name}\n"
                . "• **Room:** {$bill['room_count']}x {$booking->room_type} ({$roomNumText})\n"
                . "• **Stay Dates:** {$booking->check_in_date->format('d M Y')} – {$booking->check_out_date->format('d M Y')} ({$bill['nights']} night(s))\n"
                . "• **Status:** " . strtoupper(str_replace('_', ' ', $booking->status)) . "\n\n"
                . "💳 **Itemized Billing / Folio:**\n"
                . "• Room Charges ({$bill['nights']} night(s) × " . $fmt($bill['price_per_night']) . "): **" . $fmt($bill['subtotal']) . "**\n"
                . "• Tax & Service (21%): **" . $fmt($bill['tax']) . "**\n"
                . ($bill['extra_charges'] > 0 ? "• In-Room Dining / Spa Folio: **" . $fmt($bill['extra_charges']) . "**\n{$extraOrdersText}" : "• Additional Services: **IDR 0**\n")
                . "• Refundable Security Deposit: **" . $fmt($bill['deposit']) . "** *(returned at check-out)*\n"
                . "• **Total Bill:** **" . $fmt($bill['grand_total']) . "**\n"
                . "• **Payment Status:** {$payLabel}\n\n"
                . "🗝️ **Check-in & Check-out Guide:** Check-in starts at **14:00 WIB** (please bring valid ID/Passport). Check-out is by **12:00 WIB** at the Level 1 Front Desk — simply return your room key for a quick 3–5 minute room check and settlement.";
        } else {
            $content = "✅ **Detail Reservasi & Rincian Tagihan Terverifikasi**\n\n"
                . "• **Kode Booking:** `{$booking->booking_code}`\n"
                . "• **Nama Tamu:** {$booking->guest_name}\n"
                . "• **Kamar:** {$bill['room_count']}x {$booking->room_type} ({$roomNumText})\n"
                . "• **Periode Menginap:** {$booking->check_in_date->format('d M Y')} s/d {$booking->check_out_date->format('d M Y')} ({$bill['nights']} malam)\n"
                . "• **Status Reservasi:** " . strtoupper(str_replace('_', ' ', $booking->status)) . "\n\n"
                . "💳 **Rincian Tagihan (Folio):**\n"
                . "• Biaya Kamar ({$bill['nights']} malam × " . $fmt($bill['price_per_night']) . "): **" . $fmt($bill['subtotal']) . "**\n"
                . "• Pajak & Layanan (21%): **" . $fmt($bill['tax']) . "**\n"
                . ($bill['extra_charges'] > 0 ? "• Layanan Tambahan (F&B / Spa): **" . $fmt($bill['extra_charges']) . "**\n{$extraOrdersText}" : "• Layanan Tambahan: **IDR 0**\n")
                . "• Deposit Keamanan: **" . $fmt($bill['deposit']) . "** *(dikembalikan penuh saat check-out)*\n"
                . "• **Total Tagihan:** **" . $fmt($bill['grand_total']) . "**\n"
                . "• **Status Pembayaran:** {$payLabel}\n\n"
                . "🗝️ **Panduan Check-in & Check-out:** Check-in mulai pukul **14.00 WIB** (mohon bawa KTP/Paspor asli). Untuk check-out maksimal pukul **12.00 WIB**, Bapak/Ibu cukup menyerahkan kartu kunci di Resepsionis Lantai 1 untuk pemeriksaan singkat kamar (3–5 menit) dan pelunasan. Ada lagi yang bisa saya bantu?";
        }

        return [
            'content' => $content,
            'facility' => null,
            'special_action' => [
                'type' => 'reservation_detail',
                'booking_code' => $booking->booking_code,
                'total' => $bill['grand_total'],
                'payment_status' => $bill['payment_status'],
            ],
            'source' => 'system_reservation_verified',
        ];
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
     * Fallback cerdas berbasis Database Resmi Hotel & bebas pengulangan jika API offline.
     */
    protected function generateSimulatedHotelResponse(string $message, string $locale = 'id', array $history = [], bool $voiceMode = false): array
    {
        $lower = mb_strtolower(trim($message));
        $isFollowUp = count($history) > 0;
        $facility = null;

        // Guardrail PRD 3.3: Permintaan diskon / pengecualian kebijakan tidak boleh dijanjikan AI
        if (str_contains($lower, 'diskon') || str_contains($lower, 'potongan harga') || str_contains($lower, 'discount') || str_contains($lower, 'free upgrade') || str_contains($lower, 'gratis upgrade')) {
            $content = $locale === 'en'
                ? "Thank you for asking! As a virtual concierge, I am not able to authorize special discounts or policy exceptions directly. However, I'd be happy to connect you with our Front Office team via **ext. 0** or WhatsApp **+62 812-3456-7890** so they can check our best available promotional rates for your dates."
                : "Terima kasih sudah bertanya, Bapak/Ibu! Sebagai asisten virtual, saya belum memiliki kewenangan untuk memberikan diskon khusus atau pengecualian kebijakan secara langsung. Tapi dengan senang hati saya arahkan ke tim Reservasi kami di **ext. 0** atau WhatsApp **+62 812-3456-7890** supaya staf kami bisa bantu cek promo terbaik yang sedang berlaku ya.";

            return ['content' => $content, 'facility' => null, 'special_action' => null, 'source' => 'simulated_guardrail'];
        }

        // Proactive Upselling jika tamu menyebut ulang tahun / honeymoon
        if (str_contains($lower, 'ulang tahun') || str_contains($lower, 'birthday') || str_contains($lower, 'honeymoon') || str_contains($lower, 'bulan madu') || str_contains($lower, 'anniversary') || str_contains($lower, 'merayakan')) {
            $content = ($locale === 'en')
                ? "🎉 **Special Celebration detected! Happy Anniversary / Birthday!** 🥂\n\n"
                . "To make your celebration unforgettable, we have an exclusive offer:\n"
                . "• **Romantic Room Setup & Artisanal Cake:** Only IDR 350,000++ (Includes fresh rose petals and custom message).\n"
                . "• **Special Suite Upgrade:** Ask our team about upgrading to our **Executive Club Suite** with complimentary Evening Cocktails on Level 21.\n\n"
                . "Would you like me to note this special decoration setup for your stay?"
                : "Wah, selamat merayakan momen spesialnya ya Bapak/Ibu! 🎉🥂\n\n"
                . "Agar perayaan di Aegora Hotel semakin berkesan, kami punya pilihan menarik yang bisa kami bantu siapkan:\n"
                . "• **Paket Dekorasi Romantis & Cake Artisanal:** IDR 350.000++ (taburan kelopak mawar segar, lilin aromatik, dan kue cokelat artisanal).\n"
                . "• **Upgrade Executive Club Suite:** Nikmati ruang tamu terpisah dan akses Club Lounge Lantai 21.\n\n"
                . "Apakah Bapak/Ibu ingin saya bantu catatkan paket dekorasi spesial ini?";

            return [
                'content' => $content,
                'facility' => null,
                'special_action' => null,
                'source' => 'simulated_upselling'
            ];
        }

        // Ambil data live dari MySQL Database (Rooms & HotelKnowledge)
        $rooms = collect();
        $knowledge = collect();
        try {
            $rooms = Room::where('is_available', true)->orderBy('price_per_night')->get();
            $knowledge = HotelKnowledge::where('is_active', true)->orderBy('sort_order')->get();
        } catch (Throwable) {
            // Fallback safe
        }

        // 1. Check-in / Check-out / Early / Late Check-in (PRD Modul 4: CI-01, CI-04, CI-06)
        if (str_contains($lower, 'check-in') || str_contains($lower, 'check in') || str_contains($lower, 'checkin') || str_contains($lower, 'check-out') || str_contains($lower, 'check out') || str_contains($lower, 'checkout') || str_contains($lower, 'deposit') || str_contains($lower, 'bawa apa')) {
            $hoursInfo = $knowledge->firstWhere('category', 'hours');
            $checkoutFaq = $knowledge->first(fn ($k) => str_contains(mb_strtolower($k->title), 'check-out') && $k->category === 'faq');

            if ($hoursInfo) {
                $body = $hoursInfo->getLocalizedContent($locale);
                $extra = $checkoutFaq ? "\n\n" . $checkoutFaq->getLocalizedContent($locale) : '';
                $greeting = $isFollowUp ? '' : ($locale === 'en' ? "Welcome to Aegora Hotel! " : "Halo, selamat datang di Aegora Hotel! ");
                $closing = $locale === 'en'
                    ? "\n\nIf you'd like me to look up your reservation or billing summary, just share your **booking code and last name**."
                    : "\n\nKalau Bapak/Ibu ingin saya bantu cek detail reservasi atau tagihan, cukup kirimkan **kode booking dan nama belakang** ya.";

                return [
                    'content' => "{$greeting}**{$hoursInfo->title}**\n\n{$body}{$extra}{$closing}",
                    'facility' => null,
                    'special_action' => null,
                    'source' => 'simulated_db_knowledge',
                ];
            }
        }

        // 2. Kebijakan Hotel: Pembatalan, Hewan Peliharaan / Kucing, Anak-anak, Merokok (PRD IN-05)
        if (str_contains($lower, 'kebijakan') || str_contains($lower, 'aturan') || str_contains($lower, 'batal') || str_contains($lower, 'cancel') || str_contains($lower, 'kucing') || str_contains($lower, 'anjing') || str_contains($lower, 'hewan') || str_contains($lower, 'pet') || str_contains($lower, 'rokok') || str_contains($lower, 'smok') || str_contains($lower, 'anak') || str_contains($lower, 'extra bed')) {
            $policyInfo = $knowledge->firstWhere('category', 'policy');
            if ($policyInfo) {
                return [
                    'content' => "**{$policyInfo->title}**\n\n" . $policyInfo->getLocalizedContent($locale) . ($locale === 'en' ? "\n\nIs there anything else I can clarify for you?" : "\n\nApakah ada aturan lain yang ingin Bapak/Ibu tanyakan?"),
                    'facility' => null,
                    'special_action' => null,
                    'source' => 'simulated_db_knowledge',
                ];
            }
        }

        // 3. Transportasi / Airport Shuttle / Alamat & Lokasi (PRD IN-04, IN-07)
        if (str_contains($lower, 'shuttle') || str_contains($lower, 'bandara') || str_contains($lower, 'airport') || str_contains($lower, 'stasiun') || str_contains($lower, 'alamat') || str_contains($lower, 'lokasi') || str_contains($lower, 'location') || str_contains($lower, 'address')) {
            $item = str_contains($lower, 'shuttle') || str_contains($lower, 'bandara') || str_contains($lower, 'airport')
                ? $knowledge->firstWhere('category', 'transport')
                : $knowledge->firstWhere('category', 'profile');

            if ($item) {
                $meta = $item->operating_hours ? "\n• **Jadwal:** {$item->operating_hours}" : '';
                $loc = $item->location ? "\n• **Lokasi:** {$item->location}" : '';
                return [
                    'content' => "**{$item->title}**\n\n" . $item->getLocalizedContent($locale) . "{$loc}{$meta}",
                    'facility' => null,
                    'special_action' => null,
                    'source' => 'simulated_db_knowledge',
                ];
            }
        }

        // 4. Wi-Fi, Parkir, Gym, Spa, Meeting Room (PRD IN-01)
        if (str_contains($lower, 'wifi') || str_contains($lower, 'wi-fi') || str_contains($lower, 'parkir') || str_contains($lower, 'parking') || str_contains($lower, 'meeting')) {
            $wifiInfo = $knowledge->first(fn ($k) => str_contains(mb_strtolower($k->title), 'wi-fi') || str_contains(mb_strtolower($k->title), 'parkir'));
            if ($wifiInfo) {
                return [
                    'content' => "**{$wifiInfo->title}**\n\n" . $wifiInfo->getLocalizedContent($locale) . ($wifiInfo->location ? "\n• **Lokasi:** {$wifiInfo->location}" : ''),
                    'facility' => null,
                    'special_action' => null,
                    'source' => 'simulated_db_knowledge',
                ];
            }
        }

        if (str_contains($lower, 'gym') || str_contains($lower, 'fitness') || str_contains($lower, 'olahraga')) {
            $gymInfo = $knowledge->first(fn ($k) => str_contains(mb_strtolower($k->title), 'fitness') || str_contains(mb_strtolower($k->title), 'gym'));
            if ($gymInfo) {
                return [
                    'content' => "**{$gymInfo->title}**\n\n" . $gymInfo->getLocalizedContent($locale) . "\n• **Lokasi:** {$gymInfo->location}\n• **Jam Buka:** {$gymInfo->operating_hours}",
                    'facility' => null,
                    'special_action' => null,
                    'source' => 'simulated_db_knowledge',
                ];
            }
        }

        if (str_contains($lower, 'spa') || str_contains($lower, 'pijat') || str_contains($lower, 'massage') || str_contains($lower, 'aromaterapi')) {
            $spaInfo = $knowledge->first(fn ($k) => str_contains(mb_strtolower($k->title), 'spa'));
            if ($spaInfo) {
                return [
                    'content' => "**{$spaInfo->title}**\n\n" . $spaInfo->getLocalizedContent($locale) . "\n• **Lokasi:** {$spaInfo->location}\n• **Jam Operasional:** {$spaInfo->operating_hours}\n• **Tarif:** {$spaInfo->extra_fee}",
                    'facility' => null,
                    'special_action' => null,
                    'source' => 'simulated_db_knowledge',
                ];
            }
        }

        // 5. Kontak Staf / Resepsionis (PRD CH-08, IN-06)
        if (str_contains($lower, 'staf') || str_contains($lower, 'staff') || str_contains($lower, 'resepsionis') || str_contains($lower, 'reception') || str_contains($lower, 'human') || str_contains($lower, 'kontak') || str_contains($lower, 'telepon')) {
            $contactInfo = $knowledge->firstWhere('category', 'contact');
            $body = $contactInfo ? $contactInfo->getLocalizedContent($locale) : 'Front Desk ext. 0 atau WhatsApp +62 812-3456-7890.';
            return [
                'content' => ($locale === 'en'
                    ? "Of course! You can connect with our Front Desk & Concierge team anytime:\n\n{$body}\n\nI'll also keep the summary of our conversation ready so you won't need to repeat yourself."
                    : "Tentu Bapak/Ibu, tim Front Desk & Concierge kami siap melayani 24 jam:\n\n{$body}\n\nAnda juga bisa klik tombol **Hubungi Staf** di bagian atas halaman agar tim kami segera menghubungi Anda dengan ringkasan obrolan ini."),
                'facility' => null,
                'special_action' => null,
                'source' => 'simulated_db_knowledge',
            ];
        }

        // 6. Kolam Renang & Sarapan / Restoran (PRD IN-01, IN-02)
        if (str_contains($lower, 'kolam') || str_contains($lower, 'pool') || str_contains($lower, 'sarapan') || str_contains($lower, 'breakfast') || str_contains($lower, 'restoran') || str_contains($lower, 'restaurant') || str_contains($lower, 'makan') || str_contains($lower, 'fasilitas') || str_contains($lower, 'facilities')) {
            $poolInfo = $knowledge->first(fn ($k) => str_contains(mb_strtolower($k->title), 'pool'));
            $restInfo = $knowledge->firstWhere('category', 'restaurant');

            $poolHours = $poolInfo?->operating_hours ?? ($locale === 'en' ? '07:00 – 20:00 WIB' : '07.00 – 20.00 WIB');
            $poolLoc = $poolInfo?->location ?? ($locale === 'en' ? 'Level 3 South Deck' : 'Lantai 3 Deck Selatan');

            if ($locale === 'en') {
                $content = ($isFollowUp ? '' : "Welcome to Aegora Hotel! 🌿\n\n")
                    . ($restInfo ? "**{$restInfo->title}:** " . $restInfo->getLocalizedContent('en') . "\n\n" : "Breakfast is served daily at **The Verandah Restaurant** from **06:30 – 10:30 WIB**.\n\n")
                    . ($poolInfo ? "**{$poolInfo->title}:** " . $poolInfo->getLocalizedContent('en') : "Our **Outdoor Infinity Pool** on Level 3 is open daily from 07:00 – 20:00 WIB.");
            } else {
                $content = ($isFollowUp ? '' : "Halo, selamat datang di Aegora Hotel! 🌿\n\n")
                    . ($restInfo ? "**{$restInfo->title}** ({$restInfo->location}): " . $restInfo->getLocalizedContent('id') . "\n\n" : "Sarapan di **The Verandah Restaurant** tersedia setiap hari pukul **06.30 – 10.30 WIB**.\n\n")
                    . ($poolInfo ? "**{$poolInfo->title}** ({$poolLoc}, {$poolHours}): " . $poolInfo->getLocalizedContent('id') : "Untuk rekreasi air, **Outdoor Infinity Pool di Lantai 3** buka setiap hari pukul 07.00 – 20.00 WIB.")
                    . "\n\nAda fasilitas lain yang ingin Bapak/Ibu ketahui?";
            }

            $facility = [
                'title' => $poolInfo?->title ?? 'Outdoor Infinity Pool & Bar',
                'location' => $poolLoc,
                'hours' => $poolHours,
                'image' => asset('images/infinity-pool.jpg'),
                'tags' => $locale === 'en'
                    ? ['Complimentary Fresh Towels', 'Kids Splash Zone', 'Poolside Mocktail Bar']
                    : ['Handuk Bersih Gratis', 'Kids Splash Area', 'Poolside Mocktail Bar'],
                'actions' => [
                    ['label' => $locale === 'en' ? 'View Dining Menu' : 'Lihat Menu Restoran', 'primary' => true, 'action' => 'menu'],
                    ['label' => $locale === 'en' ? 'Reserve Breakfast Table' : 'Pesan Meja Sarapan', 'primary' => false, 'action' => 'breakfast'],
                    ['label' => $locale === 'en' ? 'Contact Reception' : 'Hubungi Resepsionis', 'primary' => false, 'action' => 'reception']
                ]
            ];

            return ['content' => $content, 'facility' => $facility, 'special_action' => null, 'source' => 'simulated_db_knowledge'];
        }

        // 7. Daftar Kamar, Ketersediaan & Harga Resmi dari Database (PRD Modul 2: BK-01 s/d BK-04)
        if (str_contains($lower, 'kamar') || str_contains($lower, 'kmr') || str_contains($lower, 'room') || str_contains($lower, 'suite') || str_contains($lower, 'deluxe') || str_contains($lower, 'family') || str_contains($lower, 'harga') || str_contains($lower, 'tarif') || str_contains($lower, 'rate') || str_contains($lower, 'kosong') || str_contains($lower, 'tersedia') || str_contains($lower, 'available')) {
            if ($rooms->isNotEmpty()) {
                $header = $locale === 'en'
                    ? "With pleasure! Here are our available room selections at Aegora Hotel:\n\n"
                    : "Dengan senang hati! Berikut pilihan kamar resmi yang tersedia di Aegora Hotel:\n\n";
                $items = [];
                foreach ($rooms as $idx => $room) {
                    $num = $idx + 1;
                    $price = 'IDR ' . number_format((float) $room->price_per_night, 0, ',', '.');
                    $facs = is_array($room->facilities) ? implode(', ', array_slice($room->facilities, 0, 5)) : '';
                    if ($locale === 'en') {
                        $items[] = "{$num}. **{$room->name} ({$room->size_sqm} m², up to {$room->capacity} guests)** — **{$price} / night** (+{$room->tax_percent}% tax & service)\n   • {$room->bed_type} | {$facs}";
                    } else {
                        $items[] = "{$num}. **{$room->name} ({$room->size_sqm} m², maks. {$room->capacity} tamu)** — **{$price} / malam** (+{$room->tax_percent}% pajak & layanan)\n   • {$room->bed_type} | {$facs}";
                    }
                }
                $footer = $locale === 'en'
                    ? "\n\nWould you like to check availability for specific check-in/check-out dates, or type **'book a room'** to open our instant reservation form?"
                    : "\n\nKalau Bapak/Ibu sudah ada rencana tanggal check-in dan check-out, silakan ketik **'mau pesan kamar'** agar saya bukakan formulir reservasi dan hitungan total biayanya ya!";

                return [
                    'content' => $header . implode("\n\n", $items) . $footer,
                    'facility' => null,
                    'special_action' => null,
                    'source' => 'simulated_db_rooms',
                ];
            }
        }

        // 8. Default Warm Hospitality Greeting / Guidance
        if ($locale === 'en') {
            $content = ($isFollowUp ? '' : "Hello, warm greetings from Aegora Hotel! I'm **Aegora Assist**, your virtual concierge. 🌿\n\n")
                . "I'd be delighted to help you with:\n"
                . "• **Rooms & Reservations:** Room options, availability, rates & instant booking\n"
                . "• **Hotel Facilities & Policies:** Dining hours, Infinity Pool, Spa, Gym, Wi-Fi & shuttle\n"
                . "• **Check-in, Check-out & Billing:** Guides, reservation lookup & folio breakdown\n"
                . "• **In-Room Services:** Instant housekeeping amenities or room dining orders\n\n"
                . "How may I make your stay more comfortable today?";
        } else {
            $content = ($isFollowUp ? '' : "Halo, salam hangat dari Aegora Hotel! Saya **Aegora Assist**, asisten virtual Front Office yang siap menemani Bapak/Ibu. 🌿\n\n")
                . "Ada yang bisa saya bantu hari ini? Saya siap melayani:\n"
                . "• **Pilihan Kamar & Reservasi:** Cek tipe kamar, tarif resmi, hingga pemesanan langsung\n"
                . "• **Informasi Hotel:** Jadwal sarapan, kolam renang, spa, gym, shuttle, & kebijakan hotel\n"
                . "• **Check-in, Check-out & Cek Tagihan:** Panduan kedatangan serta cek reservasi terverifikasi\n"
                . "• **Layanan Kamar Instan:** Permintaan handuk/amenities atau pesanan F&B ke kamar\n\n"
                . "Silakan sampaikan saja kebutuhan Bapak/Ibu ya!";
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
