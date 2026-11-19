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

    public function __construct()
    {
        $this->apiKey = config('netra.api_key', '');
        $this->baseUrl = rtrim(config('netra.base_url', 'https://api.netra.ai/v1'), '/');
        $this->model = config('netra.model', 'netra-chat-v1');
        $this->timeout = config('netra.timeout', 15);
    }

    /**
     * Generate response for user input.
     *
     * @param string $message
     * @param array $history
     * @return array{content: string, facility: array|null, source: string}
     */
    public function generateResponse(string $message, array $history = []): array
    {
        // If API Key is provided, attempt Netra API call
        if (!empty($this->apiKey)) {
            try {
                $response = $this->callNetraApi($message, $history);
                if ($response) {
                    return [
                        'content' => $response,
                        'facility' => $this->detectFacilityAttachment($message, $response),
                        'source' => 'netra_api'
                    ];
                }
            } catch (Throwable $e) {
                Log::warning('Netra API call failed, falling back to simulated response: ' . $e->getMessage());
            }
        }

        // Contextual AI Hotel Concierge Simulation
        return $this->generateSimulatedHotelResponse($message);
    }

    /**
     * Call Netra API endpoint.
     */
    protected function callNetraApi(string $message, array $history): ?string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'Anda adalah Aegora Assist, asisten virtual pribadi dan AI Concierge resmi di Aegora Hotel. '
                    . 'Berikan jawaban yang sangat ramah, hangat, profesional, dan membantu tamu seputar reservasi kamar, '
                    . 'fasilitas restoran, kolam renang, spa, gym, jadwal check-in/check-out, serta layanan hotel lainnya. '
                    . 'Gunakan Bahasa Indonesia yang sopan dan terstruktur rapi dengan markdown bold/list.'
            ]
        ];

        foreach ($history as $item) {
            $messages[] = [
                'role' => $item['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $item['content']
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout($this->timeout)->post($this->baseUrl . '/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        }

        Log::error('Netra API response error: ' . $response->body());
        return null;
    }

    /**
     * Provide highly accurate simulated responses based on hotel domain knowledge.
     */
    protected function generateSimulatedHotelResponse(string $message): array
    {
        $lower = mb_strtolower(trim($message));
        $facility = null;

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
        } elseif (str_contains($lower, 'shuttle') || str_contains($lower, 'bandara') || str_contains($lower, 'airport') || str_contains($lower, 'antar')) {
            $content = "Aegora Hotel menyediakan layanan **Airport Shuttle VIP** (Bandara Internasional – Hotel PP):\n\n"
                . "• **Jadwal Keberangkatan dari Lobi:** Pukul 06.00, 08.00, 10.00, 12.00, 14.00, 16.00, 18.00, dan 20.00 WIB.\n"
                . "• **Tarif:** Gratis untuk tamu kamar tipe Executive Suite & Tamu Member Aegora Privilege. Untuk tipe kamar lainnya IDR 150.000 net/orang.\n"
                . "• Pemesanan tempat disarankan minimal 2 jam sebelum keberangkatan melalui tombol *Hubungi Staf* atau ext. 0.";
        } elseif (str_contains($lower, 'spa') || str_contains($lower, 'pijat') || str_contains($lower, 'massage') || str_contains($lower, 'aromaterapi') || str_contains($lower, 'gym')) {
            $content = "Relaksasi paripurna menanti Anda di **Aegora Wellness Spa & Fitness** (Lantai 5):\n\n"
                . "• **Jam Operasional:** 09.00 – 22.00 WIB (Pemesanan terakhir pukul 20.30 WIB).\n"
                . "• **Paket Unggulan:**\n"
                . "  1. *Royal Javanese Aromatherapy* (90 menit) — IDR 480.000++\n"
                . "  2. *Deep Tissue Muscle Release* (60 menit) — IDR 390.000++\n"
                . "  3. *Revitalizing Herbal Scrub & Bath* (120 menit) — IDR 620.000++\n\n"
                . "Fasilitas fitness center (Gym) gratis untuk seluruh tamu hotel yang menginap, buka 24 jam dengan akses kartu kamar.";
        } elseif (str_contains($lower, 'staf') || str_contains($lower, 'resepsionis') || str_contains($lower, 'front desk') || str_contains($lower, 'telepon') || str_contains($lower, 'hubungi')) {
            $content = "Tim keramahan kami selalu siap membantu Anda kapan saja:\n\n"
                . "• **Telepon Kamar:** Cukup angkat gagang dan tekan **0** (Front Desk) atau **1** (Housekeeping).\n"
                . "• **WhatsApp Resmi Concierge:** +62 812-3456-7890 (Layanan 24 Jam).\n"
                . "• **Duty Manager On Duty:** Siaga di area lobi utama jika Anda membutuhkan asistensi khusus tatap muka.";
        } else {
            $content = "Terima kasih atas pertanyaan Anda di Aegora Assist 🌟\n\n"
                . "Sebagai asisten hotel Anda, saya dapat memberikan informasi lengkap seputar:\n"
                . "• Reservasi & tarif kamar (Deluxe, Suite)\n"
                . "• Jam buka sarapan di The Verandah Restaurant\n"
                . "• Outdoor Infinity Pool & Bar di Lantai 3\n"
                . "• Prosedur Check-in & Check-out Ekspres\n"
                . "• Password Wi-Fi & Fasilitas Spa / Gym\n"
                . "• Menghubungkan Anda langsung ke Resepsionis\n\n"
                . "Silakan sampaikan kebutuhan Anda atau pilih pertanyaan rekomendasi di bawah ini!";
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
    protected function detectFacilityAttachment(string $query, string $response): ?array
    {
        $text = mb_strtolower($query . ' ' . $response);
        if (str_contains($text, 'infinity pool') || str_contains($text, 'kolam renang') || str_contains($text, 'verandah')) {
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
