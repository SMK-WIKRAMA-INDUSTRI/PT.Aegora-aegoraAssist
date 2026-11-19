<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     * Generate response for user input with multi-language support.
     *
     * @param string $message
     * @param array $history
     * @param string $locale ('id' or 'en')
     * @return array{content: string, facility: array|null, source: string}
     */
    public function generateResponse(string $message, array $history = [], string $locale = 'id'): array
    {
        // Jika API Key sudah disediakan di .env, kirim request ke Netra Runtime (model DeepSeek)
        if (!empty($this->apiKey) && $this->apiKey !== 'YOUR_NETRA_API_KEY_HERE') {
            try {
                $response = $this->callNetraApi($message, $history, $locale);
                if ($response) {
                    return [
                        'content' => $response,
                        'facility' => $this->detectFacilityAttachment($message, $response, $locale),
                        'source' => 'netra_deepseek'
                    ];
                }
            } catch (Throwable $e) {
                Log::warning('Netra Runtime (DeepSeek) call error: ' . $e->getMessage());
            }
        }

        // Fallback cerdas bilingual jika provider sedang gangguan atau key belum diset
        $simulated = $this->generateSimulatedHotelResponse($message, $locale);

        if (empty($this->apiKey) || $this->apiKey === 'YOUR_NETRA_API_KEY_HERE') {
            $simulated['source'] = 'simulated_no_key';
        }

        return $simulated;
    }

    /**
     * Get system prompt tailored to the requested language.
     */
    protected function getLocalizedSystemPrompt(string $locale): string
    {
        if ($locale === 'en') {
            return "You are 'Aegora Assist', the professional Customer Service Agent & AI Concierge at Aegora Hotel & Hospitality.\n" .
                "Your role is to assist guests and prospective visitors with utmost warmth, elegance, polite hospitality, and proactive solutions.\n\n" .
                "Customer Service Guidelines:\n" .
                "1. Greet guests warmly and professionally in English.\n" .
                "2. Provide accurate information regarding room reservations (Deluxe King/Twin from IDR 1,250,000++, Executive Club Suite from IDR 2,450,000++ with 21st Fl Club Lounge access), breakfast at The Verandah Restaurant (06:30 – 10:30 WIB), Outdoor Infinity Pool & Bar on Level 3 (07:00 – 22:00 WIB), Aegora Wellness Spa & Fitness on Level 5, express check-in (from 14:00 WIB) and express check-out (12:00 WIB), airport VIP shuttles, and free high-speed Wi-Fi (SSID: Aegora_Guest).\n" .
                "3. Use elegant, natural English with clean Markdown formatting (bold keywords and bullet points) for readability.\n" .
                "4. If a guest writes in another language (such as Japanese, Mandarin, Arabic, Spanish, French, or Indonesian), smoothly reply in that guest's language with the same warm 5-star courtesy.\n" .
                "5. For tasks requiring physical assistance, urgent complaints, or bellboy requests, kindly advise the guest to dial 0 on their in-room telephone or message the 24/7 Concierge WhatsApp at +62 812-3456-7890.\n" .
                "6. Maintain awareness of multi-turn conversation context.";
        }

        return !empty($this->systemPrompt) ? $this->systemPrompt : (
            "Anda adalah 'Aegora Assist', Customer Service Agent & AI Concierge profesional di Aegora Hotel & Hospitality.\n" .
            "Tugas utama Anda adalah melayani tamu dan calon tamu dengan sangat ramah, hangat, sopan, empatik, dan solutif layaknya front office/concierge hotel bintang lima.\n\n" .
            "Panduan Pelayanan CS Agent:\n" .
            "1. Berikan salam hangat dan perkenalkan diri dengan sopan jika relevan.\n" .
            "2. Jawab pertanyaan seputar reservasi kamar, tipe kamar (Deluxe, Suite), fasilitas (The Verandah Restaurant, Outdoor Infinity Pool di Lt 3, Aegora Spa di Lt 5, Fitness Center), prosedur check-in (14.00 WIB) & check-out (12.00 WIB), airport shuttle, dan Wi-Fi hotel.\n" .
            "3. Gunakan Bahasa Indonesia yang luwes, profesional, dan gunakan pemformatan Markdown (seperti bold dan bullet points) agar informasi mudah dibaca tamu.\n" .
            "4. Jika tamu berkomunikasi dalam bahasa lain (Inggris, Mandarin, Jepang, dsb.), balaslah dengan bahasa yang sesuai dengan penuh keramahan.\n" .
            "5. Jika ada permintaan khusus di luar kewenangan AI (seperti komplain mendesak atau permintaan staf fisik ke kamar), arahkan tamu dengan ramah untuk menekan angka 0 pada telepon kamar (Front Desk) atau menghubungi WhatsApp resmi Concierge di +62 812-3456-7890.\n" .
            "6. Selalu bersikap responsif terhadap riwayat percakapan sebelumnya."
        );
    }

    /**
     * Call Netra Runtime endpoint with DeepSeek model.
     */
    protected function callNetraApi(string $message, array $history, string $locale = 'id'): ?string
    {
        $messages = [];

        // 1. System Prompt disesuaikan dengan bahasa pilihan
        $prompt = $this->getLocalizedSystemPrompt($locale);
        $messages[] = [
            'role' => 'system',
            'content' => $prompt,
        ];

        // 2. Chat History (Multi-turn conversations)
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

        // Format endpoint URL secara aman
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

            // Format standar OpenAI / Netra Runtime
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            if (isset($data['choices'][0]['text'])) {
                return trim($data['choices'][0]['text']);
            }

            if (isset($data['content'])) {
                return trim($data['content']);
            }

            if (isset($data['response'])) {
                return trim($data['response']);
            }

            Log::warning('Netra API returned unexpected response structure: ' . $response->body());
        } else {
            Log::error('Netra API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return null;
    }

    /**
     * Provide accurate responses based on hotel domain knowledge in ID or EN.
     */
    protected function generateSimulatedHotelResponse(string $message, string $locale = 'id'): array
    {
        $lower = mb_strtolower(trim($message));
        $facility = null;

        if ($locale === 'en') {
            if (str_contains($lower, 'pool') || str_contains($lower, 'swim') || str_contains($lower, 'breakfast') || str_contains($lower, 'dining') || str_contains($lower, 'restaurant')) {
                $content = "Welcome to Aegora Hotel 🌿\n\n"
                    . "Breakfast is served daily at **The Verandah Restaurant** from **06:30 AM – 10:30 AM WIB**, featuring an artisanal buffet of authentic Indonesian delicacies and international favorites.\n\n"
                    . "For aquatic relaxation, our **Outdoor Infinity Pool & Bar on Level 3** offers panoramic skyline views and refreshing poolside refreshments.";
                
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
            } elseif (str_contains($lower, 'room') || str_contains($lower, 'suite') || str_contains($lower, 'deluxe') || str_contains($lower, 'rate') || str_contains($lower, 'price') || str_contains($lower, 'book')) {
                $content = "We have wonderful room options available for your stay:\n\n"
                    . "1. **Deluxe King / Twin (36 m²)**\n"
                    . "   • Rates start from **IDR 1,250,000++ / night**\n"
                    . "   • Features King Koil plush bedding, walk-in rain shower, 55\" Smart TV, and complimentary high-speed Wi-Fi.\n\n"
                    . "2. **Executive Club Suite (58 m²)**\n"
                    . "   • Rates start from **IDR 2,450,000++ / night**\n"
                    . "   • Includes exclusive access to the 21st Floor Executive Club Lounge (Complimentary Evening Cocktails & Canapés), marble soaking bathtub, and personal Nespresso machine.\n\n"
                    . "Would you like me to connect you with our reservations team to secure your booking?";
            } elseif (str_contains($lower, 'check-out') || str_contains($lower, 'checkout') || str_contains($lower, 'express')) {
                $content = "Here is our **Express Check-out Guide**:\n\n"
                    . "• **Standard Check-out Time:** **12:00 PM (Noon) WIB**.\n"
                    . "• **Express Procedure:** Simply drop your room keycards into the *Express Drop Box* at the main lobby or Concierge desk.\n"
                    . "• **e-Folio Billing:** Your finalized transaction summary and receipt will be automatically sent to your registered email within 15 minutes.\n"
                    . "• If you require luggage assistance (bellboy), please dial extension 0 from your in-room phone.";
            } elseif (str_contains($lower, 'check-in') || str_contains($lower, 'checkin') || str_contains($lower, 'deposit')) {
                $content = "**Check-in & Deposit Information** at Aegora Hotel:\n\n"
                    . "• **Check-in Time:** Starting from **02:00 PM WIB** (Early check-in subject to room availability).\n"
                    . "• **Incidental Deposit:** Standard deposit of **IDR 500,000 / night** (via credit card pre-authorization or cash, fully refunded upon check-out).\n"
                    . "• **Requirement:** A valid government-issued photo ID (Passport / KTP) matching the reservation name.";
            } elseif (str_contains($lower, 'wifi') || str_contains($lower, 'internet') || str_contains($lower, 'password')) {
                $content = "High-speed fiber-optic Wi-Fi is available across all hotel areas:\n\n"
                    . "• **Network (SSID):** `Aegora_Guest`\n"
                    . "• **Password:** `AegoraLuxuryStay2026`\n"
                    . "• Or log in through our portal using your **Room Number** and **Last Name**.\n\n"
                    . "Speeds up to 100 Mbps with unlimited quota for your streaming and business needs.";
            } else {
                $content = "Thank you for contacting Aegora Assist 🌟\n\n"
                    . "As your personal AI Concierge, I am delighted to help you with:\n"
                    . "• **Room Reservations & Suites** (Deluxe & Executive Suite)\n"
                    . "• **Breakfast & Dining** at The Verandah Restaurant\n"
                    . "• **Hotel Facilities** (Infinity Pool Level 3, Spa & Gym Level 5)\n"
                    . "• **Express Check-in & Check-out Procedures**\n"
                    . "• **Wi-Fi Details & VIP Airport Transfers**\n\n"
                    . "How may I assist you to make your stay extraordinary today?";
            }

            return [
                'content' => $content,
                'facility' => $facility,
                'source' => 'simulated'
            ];
        }

        // Bahasa Indonesia default
        if (str_contains($lower, 'kolam') || str_contains($lower, 'pool') || str_contains($lower, 'renang') || str_contains($lower, 'sarapan') || str_contains($lower, 'makan') || str_contains($lower, 'restoran')) {
            $content = "Halo! Selamat datang di Aegora Hotel 🌿\n\n"
                . "Sarapan pagi di **The Verandah Restaurant** tersedia setiap hari pukul **06.30 – 10.30 WIB** dengan konsep prasmanan masakan autentik Nusantara serta hidangan artisanal internasional segar.\n\n"
                . "Untuk rekreasi air, kami menyediakan **Outdoor Infinity Pool & Bar di Lantai 3** dengan panorama kota spektakuler.";
            
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
        } elseif (str_contains($lower, 'kamar') || str_contains($lower, 'deluxe') || str_contains($lower, 'executive') || str_contains($lower, 'tipe') || str_contains($lower, 'reservasi') || str_contains($lower, 'tarif') || str_contains($lower, 'harga')) {
            $content = "Untuk menginap hari ini, kami memiliki ketersediaan kamar pilihan terbaik untuk Anda:\n\n"
                . "1. **Deluxe King / Twin (36 m²)**\n"
                . "   • Tarif mulai **IDR 1.250.000++ / malam**\n"
                . "   • Tempat tidur King Koil, walk-in rain shower, pemandangan kota, Smart TV 55\", dan gratis Wi-Fi berkecepatan tinggi.\n\n"
                . "2. **Executive Club Suite (58 m²)**\n"
                . "   • Tarif mulai **IDR 2.450.000++ / malam**\n"
                . "   • Termasuk akses eksklusif ke Executive Club Lounge di Lantai 21 (Free Evening Cocktails & Canapés), bathtub marmer, dan mesin kopi Nespresso pribadi.\n\n"
                . "Apakah Anda ingin saya hubungkan ke tim reservasi untuk melakukan pemesanan sekarang?";
        } elseif (str_contains($lower, 'check-out') || str_contains($lower, 'checkout') || str_contains($lower, 'ekspres')) {
            $content = "Berikut adalah panduan **Check-out Ekspres** di Aegora Hotel:\n\n"
                . "• **Batas Waktu Standar:** Pukul **12.00 WIB**.\n"
                . "• **Prosedur Ekspres:** Anda cukup meninggalkan kartu kunci (keycard) di *Express Drop Box* yang tersedia di lobi utama atau meja Concierge.\n"
                . "• **Rincian Tagihan (e-Folio):** Bukti pembayaran dan rincian transaksi final akan otomatis kami kirimkan ke alamat email terdaftar Anda dalam waktu 15 menit setelah kartu dikembalikan.\n"
                . "• Jika memerlukan layanan angkut bagasi (*bellboy*), silakan hubungi ext. 0 dari telepon kamar.";
        } elseif (str_contains($lower, 'check-in') || str_contains($lower, 'checkin') || str_contains($lower, 'deposit') || str_contains($lower, 'tagihan')) {
            $content = "Informasi prosedur **Check-in & Deposit** Aegora Hotel:\n\n"
                . "• **Jadwal Check-in:** Mulai pukul **14.00 WIB** (Early check-in tergantung ketersediaan kamar).\n"
                . "• **Deposit Kamar:** Standar deposit sebesar **IDR 500.000 / malam** (dapat menggunakan kartu kredit pre-authorization atau uang tunai, dikembalikan penuh saat check-out).\n"
                . "• **Syarat:** Menunjukkan kartu identitas resmi (KTP / Paspor) yang sesuai dengan nama pada reservasi.";
        } elseif (str_contains($lower, 'wifi') || str_contains($lower, 'wi-fi') || str_contains($lower, 'internet') || str_contains($lower, 'password')) {
            $content = "Layanan internet serat optik berkecepatan tinggi tersedia di seluruh area hotel:\n\n"
                . "• **Nama Jaringan (SSID):** `Aegora_Guest`\n"
                . "• **Password:** `AegoraLuxuryStay2026`\n"
                . "• Atau gunakan portal login dengan memasukkan **Nomor Kamar** dan **Nama Belakang** tamu terdaftar.\n\n"
                . "Kecepatan akses hingga 100 Mbps tanpa batasan kuota untuk kenyamanan streaming & pekerjaan Anda.";
        } else {
            $content = "Terima kasih telah menghubungi Customer Service Aegora Assist 🌟\n\n"
                . "Saya siap membantu Anda dengan informasi lengkap seputar:\n"
                . "• **Reservasi & Tipe Kamar** (Deluxe & Executive Suite)\n"
                . "• **Restoran & Sarapan Pagi** di The Verandah Restaurant\n"
                . "• **Fasilitas Hotel** (Outdoor Infinity Pool Lt. 3, Spa & Gym Lt. 5)\n"
                . "• **Layanan Check-in & Check-out Ekspres**\n"
                . "• **Koneksi Wi-Fi & Fasilitas Penjemputan Bandara**\n\n"
                . "Ada yang bisa saya bantu untuk kenyamanan masa menginap Anda hari ini?";
        }

        return [
            'content' => $content,
            'facility' => $facility,
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
            if ($locale === 'en') {
                return [
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
            }

            return [
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
        }
        return null;
    }
}
