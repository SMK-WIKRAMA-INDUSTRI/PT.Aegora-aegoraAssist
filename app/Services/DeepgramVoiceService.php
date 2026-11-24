<?php

namespace App\Services;

use App\Models\HotelSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeepgramVoiceService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = $this->resolveApiKey();
        $this->baseUrl = rtrim((string) config('deepgram.base_url', 'https://api.deepgram.com'), '/');
        $this->timeout = (int) config('deepgram.timeout', 25);
    }

    /**
     * Resolve Deepgram API key dynamically from config (.env) or optional DB setting.
     */
    protected function resolveApiKey(): string
    {
        $envKey = trim((string) config('deepgram.api_key', ''));
        if ($envKey !== '') {
            return $envKey;
        }

        try {
            return trim((string) HotelSetting::getVal('deepgram_api_key', ''));
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Check whether a usable Deepgram API key is configured.
     */
    public function isConfigured(): bool
    {
        $this->apiKey = $this->resolveApiKey();
        return $this->apiKey !== '' && $this->apiKey !== 'YOUR_DEEPGRAM_API_KEY_HERE';
    }

    /**
     * Return safe public voice configuration & Deepgram hotel CS voice presets.
     */
    public function getPublicConfig(): array
    {
        $activeIdVoice = $this->getPreferredVoice('id');
        $activeEnVoice = $this->getPreferredVoice('en');

        return [
            'provider' => 'deepgram',
            'configured' => $this->isConfigured(),
            'stt_model' => config('deepgram.stt.model', 'nova-3'),
            'active_voices' => [
                'id' => $activeIdVoice,
                'en' => $activeEnVoice,
            ],
            'voices' => config('deepgram.voices', []),
            'multilingual_map' => config('deepgram.multilingual_map', []),
        ];
    }

    /**
     * Get preferred Deepgram voice model for a language (Admin Setting -> config -> default).
     */
    public function getPreferredVoice(string $lang): string
    {
        $normalized = strtolower(substr($lang, 0, 2));
        $settingKey = $normalized === 'id' ? 'deepgram_voice_id' : 'deepgram_voice_en';
        $default = $normalized === 'id'
            ? config('deepgram.tts.default_id_voice', 'flux-fitri-id')
            : config('deepgram.tts.default_en_voice', 'aura-2-helena-en');

        try {
            $saved = HotelSetting::getVal($settingKey);
            if (!empty($saved)) {
                return (string) $saved;
            }
        } catch (Throwable) {
            // Ignore when DB unavailable
        }

        return (string) $default;
    }

    /**
     * Automatically detect the language of a text message or AI reply.
     */
    public function detectLanguage(string $text, string $fallback = 'id'): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return $fallback;
        }

        // Script-based detection for East Asian / Arabic scripts
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $trimmed)) {
            return 'ja';
        }
        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $trimmed)) {
            return 'ko';
        }
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $trimmed)) {
            return 'zh';
        }
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $trimmed)) {
            return 'ar';
        }

        $lower = ' ' . mb_strtolower($trimmed) . ' ';

        $scores = [
            'id' => 0,
            'en' => 0,
            'fr' => 0,
            'de' => 0,
            'es' => 0,
        ];

        $idMarkers = [
            ' saya ', ' aku ', ' kami ', ' anda ', ' bapak', ' ibu ', ' kak ', ' kamar ', ' harga ', ' berapa ', ' brp ',
            ' ada ', ' bisa ', ' boleh ', ' tidak ', ' gak ', ' ga ', ' nggak ', ' mau ', ' pesan ', ' sarapan ', ' kolam ',
            ' renang ', ' terima kasih', ' makasih', ' halo ', ' selamat ', ' siang ', ' pagi ', ' malam ', ' sore ',
            ' tagihan ', ' reservasi ', ' jam ', ' lantai ', ' handuk ', ' tolong ', ' minta ', ' dong ', ' ya ', ' yg ',
            ' yang ', ' untuk ', ' dengan ', ' di ', ' ke ', ' dari ', ' bagaimana ', ' gimana ', ' kapan ', ' dimana ',
            ' kmr ', ' kosong ', ' bawa ', ' kucing ', ' parkir ', ' batal', ' pembatalan ', ' apakah ', ' mohon ', ' silakan ',
        ];

        $enMarkers = [
            ' the ', ' is ', ' are ', ' was ', ' were ', ' you ', ' your ', ' would ', ' could ', ' should ', ' can i ',
            ' do you ', ' have ', ' room ', ' rooms ', ' breakfast ', ' swimming ', ' pool ', ' check-in ', ' check-out ',
            ' checkout ', ' checkin ', ' price ', ' rate ', ' rates ', ' available ', ' availability ', ' book ', ' booking ',
            ' reservation ', ' hello ', ' hi ', ' good morning', ' good afternoon', ' good evening', ' thank you', ' thanks ',
            ' please ', ' bill ', ' invoice ', ' floor ', ' towels ', ' help ', ' what ', ' when ', ' where ', ' how ', ' much ',
            ' welcome ', ' certainly', ' delighted ', ' hotel ', ' stay ', ' tonight ', ' weekend ', ' pets ', ' cancel',
        ];

        $frMarkers = [' bonjour', ' merci', ' chambre', ' petit-déjeuner', ' piscine', ' réserver', ' s\'il vous plaît', ' combien', ' pouvez-vous'];
        $deMarkers = [' guten tag', ' hallo ', ' zimmer', ' frühstück', ' schwimmbad', ' buchen', ' danke', ' bitte', ' wie viel', ' können sie'];
        $esMarkers = [' hola ', ' gracias', ' habitación', ' desayuno', ' piscina', ' reservar', ' por favor', ' cuánto', ' buenos días'];

        foreach ($idMarkers as $m) {
            if (str_contains($lower, $m)) {
                $scores['id'] += 2;
            }
        }
        foreach ($enMarkers as $m) {
            if (str_contains($lower, $m)) {
                $scores['en'] += 2;
            }
        }
        foreach ($frMarkers as $m) {
            if (str_contains($lower, $m)) {
                $scores['fr'] += 3;
            }
        }
        foreach ($deMarkers as $m) {
            if (str_contains($lower, $m)) {
                $scores['de'] += 3;
            }
        }
        foreach ($esMarkers as $m) {
            if (str_contains($lower, $m)) {
                $scores['es'] += 3;
            }
        }

        arsort($scores);
        $topLang = (string) array_key_first($scores);
        $topScore = (int) ($scores[$topLang] ?? 0);

        if ($topScore === 0) {
            return $fallback;
        }

        return $topLang;
    }

    /**
     * Resolve exact Deepgram TTS endpoint (/v2/speak Flux vs /v1/speak Aura-2) and voice model.
     *
     * @return array{endpoint: string, model: string, engine: string, lang: string}
     */
    public function resolveVoiceModel(string $lang, ?string $overrideVoice = null): array
    {
        $code = strtolower(substr($lang, 0, 2));

        // Explicit voice override requested by client or admin
        if (!empty($overrideVoice)) {
            $engine = str_starts_with($overrideVoice, 'flux-') ? 'v2' : 'v1';
            $endpoint = $engine === 'v2'
                ? $this->baseUrl . '/v2/speak'
                : $this->baseUrl . '/v1/speak';

            return [
                'endpoint' => $endpoint,
                'model' => $overrideVoice,
                'engine' => $engine,
                'lang' => $code,
            ];
        }

        // Indonesian uses Deepgram Flux TTS (/v2/speak) with warm polite concierge voice
        if ($code === 'id' || $code === 'ms') {
            $model = $this->getPreferredVoice('id');
            $engine = str_starts_with($model, 'flux-') ? 'v2' : 'v1';

            return [
                'endpoint' => $this->baseUrl . '/' . $engine . '/speak',
                'model' => $model,
                'engine' => $engine,
                'lang' => 'id',
            ];
        }

        // English uses Deepgram Aura-2 (/v1/speak)
        if ($code === 'en') {
            $model = $this->getPreferredVoice('en');
            $engine = str_starts_with($model, 'flux-') ? 'v2' : 'v1';

            return [
                'endpoint' => $this->baseUrl . '/' . $engine . '/speak',
                'model' => $model,
                'engine' => $engine,
                'lang' => 'en',
            ];
        }

        // Other international languages mapped to Aura-2 native voices
        $multiMap = config('deepgram.multilingual_map', []);
        if (isset($multiMap[$code])) {
            $entry = $multiMap[$code];
            $engine = $entry['engine'] ?? 'v1';

            return [
                'endpoint' => $this->baseUrl . '/' . $engine . '/speak',
                'model' => $entry['model'],
                'engine' => $engine,
                'lang' => $code,
            ];
        }

        // Default graceful fallback to English warm voice
        $enModel = $this->getPreferredVoice('en');

        return [
            'endpoint' => $this->baseUrl . '/v1/speak',
            'model' => $enModel,
            'engine' => 'v1',
            'lang' => $code,
        ];
    }

    /**
     * Clean and normalize text so TTS sounds warm, natural, and conversational — never robotic.
     */
    public function prepareTextForSpeech(string $text, string $lang = 'id'): string
    {
        $clean = $text;

        // Strip markdown syntax & HTML tags
        $clean = preg_replace('/```[\s\S]*?```/', ' ', $clean);
        $clean = preg_replace('/`([^`]+)`/', '$1', $clean);
        $clean = preg_replace('/\*\*([^*]+)\*\*/', '$1', $clean);
        $clean = preg_replace('/\*([^*]+)\*/', '$1', $clean);
        $clean = preg_replace('/__([^_]+)__/', '$1', $clean);
        $clean = preg_replace('/_([^_]+)_/', '$1', $clean);
        $clean = preg_replace('/#{1,6}\s*/', '', $clean);
        $clean = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $clean);
        $clean = strip_tags($clean);

        // Remove emojis and decorative symbols that sound weird in TTS
        $clean = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}\x{200D}✦•▸►→←✓✔✕✖]/u', '', $clean);

        // Convert bullet lines into natural pauses
        $clean = preg_replace('/\r?\n\s*[-*]\s*/', '. ', $clean);
        $clean = preg_replace('/\r?\n+/', '. ', $clean);

        // Naturalize hotel abbreviations depending on language
        if ($lang === 'id') {
            $replacements = [
                '/\bWIB\b/' => 'Waktu Indonesia Barat',
                '/\bLt\.\s*/i' => 'Lantai ',
                '/\bs\/d\b/i' => 'sampai dengan',
                '/\bext\.\s*/i' => 'ekstensi ',
                '/\bBpk\.\s*/i' => 'Bapak ',
                '/\bRp\s*/' => 'Rupiah ',
                '/\bIDR\s*/' => 'Rupiah ',
                '/m²/' => 'meter persegi',
                '/\+\+/' => ' plus pajak dan layanan',
                '/\bWA\b/' => 'WhatsApp',
                '/\bHP\b/' => 'handphone',
            ];
        } else {
            $replacements = [
                '/\bLt\.\s*/i' => 'Level ',
                '/\bext\.\s*/i' => 'extension ',
                '/m²/' => 'square meters',
                '/\+\+/' => ' plus tax and service',
                '/\bWIB\b/' => 'Western Indonesia Time',
            ];
        }

        $clean = preg_replace(array_keys($replacements), array_values($replacements), $clean);

        // Collapse duplicate punctuation and whitespace
        $clean = preg_replace('/\.\s*\./', '.', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        // Keep TTS payload within Deepgram single-request character limit (max ~1900 chars)
        if (mb_strlen($clean) > 1800) {
            $clean = mb_substr($clean, 0, 1797) . '...';
        }

        return $clean;
    }

    /**
     * Synthesize speech via Deepgram TTS API (Flux /v2/speak or Aura-2 /v1/speak).
     *
     * @return array{ok: bool, audio?: string, content_type?: string, model: string, lang: string, clean_text: string, error?: string, configured: bool}
     */
    public function synthesize(string $text, ?string $lang = null, ?string $overrideVoice = null): array
    {
        $detectedLang = $lang ? strtolower(substr($lang, 0, 2)) : $this->detectLanguage($text, 'id');
        $cleanText = $this->prepareTextForSpeech($text, $detectedLang);
        $resolved = $this->resolveVoiceModel($detectedLang, $overrideVoice);

        if ($cleanText === '') {
            return [
                'ok' => false,
                'configured' => $this->isConfigured(),
                'model' => $resolved['model'],
                'lang' => $resolved['lang'],
                'clean_text' => '',
                'error' => 'Teks kosong untuk disintesis.',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'configured' => false,
                'model' => $resolved['model'],
                'lang' => $resolved['lang'],
                'clean_text' => $cleanText,
                'error' => 'DEEPGRAM_API_KEY belum diisi di file .env.',
            ];
        }

        try {
            $queryParams = [
                'model' => $resolved['model'],
                'encoding' => config('deepgram.tts.encoding', 'mp3'),
            ];

            if ($resolved['engine'] === 'v2') {
                $queryParams['speed'] = config('deepgram.tts.speed', 1.0);
                $queryParams['expressivity'] = config('deepgram.tts.expressivity', 0);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg',
            ])
                ->timeout($this->timeout)
                ->post($resolved['endpoint'] . '?' . http_build_query($queryParams), [
                    'text' => $cleanText,
                ]);

            if ($response->successful() && strlen($response->body()) > 100) {
                return [
                    'ok' => true,
                    'configured' => true,
                    'audio' => $response->body(),
                    'content_type' => $response->header('Content-Type') ?: 'audio/mpeg',
                    'model' => $resolved['model'],
                    'lang' => $resolved['lang'],
                    'clean_text' => $cleanText,
                ];
            }

            // If Flux v2 returned an error (e.g., model not yet enabled on account), try Aura-2 English gracefully as secondary Deepgram attempt
            Log::warning('Deepgram TTS primary call failed', [
                'endpoint' => $resolved['endpoint'],
                'model' => $resolved['model'],
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            if ($resolved['engine'] === 'v2') {
                $fallbackModel = $this->getPreferredVoice('en');
                $retry = Http::withHeaders([
                    'Authorization' => 'Token ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'audio/mpeg',
                ])
                    ->timeout($this->timeout)
                    ->post($this->baseUrl . '/v1/speak?' . http_build_query([
                        'model' => $fallbackModel,
                        'encoding' => 'mp3',
                    ]), [
                        'text' => $cleanText,
                    ]);

                if ($retry->successful() && strlen($retry->body()) > 100) {
                    return [
                        'ok' => true,
                        'configured' => true,
                        'audio' => $retry->body(),
                        'content_type' => $retry->header('Content-Type') ?: 'audio/mpeg',
                        'model' => $fallbackModel,
                        'lang' => $resolved['lang'],
                        'clean_text' => $cleanText,
                    ];
                }
            }

            return [
                'ok' => false,
                'configured' => true,
                'model' => $resolved['model'],
                'lang' => $resolved['lang'],
                'clean_text' => $cleanText,
                'error' => 'Deepgram TTS mengembalikan status HTTP ' . $response->status(),
            ];
        } catch (Throwable $e) {
            Log::error('Deepgram TTS exception: ' . $e->getMessage());

            return [
                'ok' => false,
                'configured' => true,
                'model' => $resolved['model'],
                'lang' => $resolved['lang'],
                'clean_text' => $cleanText,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Transcribe speech audio via Deepgram Nova-3 STT with automatic language detection.
     *
     * @return array{ok: bool, transcript?: string, detected_language?: string, confidence?: float, configured: bool, error?: string}
     */
    public function transcribe(string $audioBinary, string $mimeType = 'audio/webm', ?string $hintLang = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'configured' => false,
                'error' => 'DEEPGRAM_API_KEY belum diisi di file .env.',
            ];
        }

        try {
            $query = [
                'model' => config('deepgram.stt.model', 'nova-3'),
                'smart_format' => 'true',
                'punctuate' => 'true',
                'detect_language' => 'true',
            ];

            // Note: do NOT force single language when detect_language=true so guests can switch freely between ID, EN, JA, etc.
            if ($hintLang === 'multi') {
                unset($query['detect_language']);
                $query['language'] = 'multi';
            }

            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiKey,
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->withBody($audioBinary, $mimeType ?: 'audio/webm')
                ->post($this->baseUrl . '/v1/listen?' . http_build_query($query));

            if ($response->successful()) {
                $data = $response->json();
                $channel = $data['results']['channels'][0] ?? [];
                $alt = $channel['alternatives'][0] ?? [];
                $transcript = trim((string) ($alt['transcript'] ?? ''));
                $detectedLang = $channel['detected_language']
                    ?? ($alt['languages'][0] ?? null);

                // Normalize language code to 2-letter code, refining with text detector if uncertain
                if ($detectedLang) {
                    $detectedLang = strtolower(substr((string) $detectedLang, 0, 2));
                } elseif ($transcript !== '') {
                    $detectedLang = $this->detectLanguage($transcript, 'id');
                } else {
                    $detectedLang = 'id';
                }

                return [
                    'ok' => true,
                    'configured' => true,
                    'transcript' => $transcript,
                    'detected_language' => $detectedLang,
                    'confidence' => (float) ($alt['confidence'] ?? 0),
                ];
            }

            Log::error('Deepgram STT failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return [
                'ok' => false,
                'configured' => true,
                'error' => 'Deepgram STT gagal dengan status HTTP ' . $response->status(),
            ];
        } catch (Throwable $e) {
            Log::error('Deepgram STT exception: ' . $e->getMessage());

            return [
                'ok' => false,
                'configured' => true,
                'error' => $e->getMessage(),
            ];
        }
    }
}
