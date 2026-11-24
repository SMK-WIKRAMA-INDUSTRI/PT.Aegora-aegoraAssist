<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\DeepgramVoiceService;
use App\Services\NetraChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class VoiceController extends Controller
{
    public function __construct(
        protected DeepgramVoiceService $voiceService,
        protected NetraChatService $chatService,
    ) {}

    /**
     * Return Deepgram configuration state & available hotel CS voice characters.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->voiceService->getPublicConfig(),
        ]);
    }

    /**
     * Synthesize text to natural Deepgram audio (MP3 stream) with automatic language matching.
     */
    public function speak(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|min:1|max:3000',
            'lang' => 'nullable|string|max:10',
            'voice' => 'nullable|string|max:80',
        ]);

        // Auto-detect language from text if not forced, so AI voice always matches spoken language
        $lang = $validated['lang'] ?? $this->voiceService->detectLanguage($validated['text'], 'id');

        $result = $this->voiceService->synthesize(
            $validated['text'],
            $lang,
            $validated['voice'] ?? null
        );

        if ($result['ok'] && isset($result['audio'])) {
            return response($result['audio'], 200, [
                'Content-Type' => $result['content_type'] ?? 'audio/mpeg',
                'Cache-Control' => 'no-store',
                'X-Voice-Provider' => 'deepgram',
                'X-Voice-Model' => $result['model'],
                'X-Detected-Language' => $result['lang'],
            ]);
        }

        return response()->json([
            'status' => 'fallback',
            'configured' => $result['configured'],
            'message' => $result['error'] ?? 'Deepgram TTS tidak tersedia saat ini.',
            'data' => [
                'clean_text' => $result['clean_text'] ?? '',
                'detected_language' => $result['lang'] ?? $lang,
                'recommended_model' => $result['model'] ?? null,
            ],
        ], 200);
    }

    /**
     * Transcribe recorded guest audio via Deepgram Nova-3 with automatic language detection.
     */
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|max:15360', // Max 15MB
            'hint_lang' => 'nullable|string|max:10',
        ]);

        $file = $request->file('audio');
        $mimeType = $file->getMimeType() ?: 'audio/webm';
        $binary = file_get_contents($file->getRealPath());

        $result = $this->voiceService->transcribe($binary, $mimeType, $request->input('hint_lang'));

        if ($result['ok']) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'transcript' => $result['transcript'],
                    'detected_language' => $result['detected_language'],
                    'confidence' => $result['confidence'],
                ],
            ]);
        }

        return response()->json([
            'status' => 'fallback',
            'configured' => $result['configured'],
            'message' => $result['error'] ?? 'Transkripsi Deepgram belum aktif.',
        ], 200);
    }

    /**
     * Complete Speech-to-Speech (STS) turn powered by Deepgram STT + AI Concierge + Deepgram TTS.
     */
    public function converse(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|max:15360',
            'session_id' => 'nullable|string|max:100',
            'voice' => 'nullable|string|max:80',
            'locale' => 'nullable|string|max:10',
        ]);

        $file = $request->file('audio');
        $mimeType = $file->getMimeType() ?: 'audio/webm';
        $binary = file_get_contents($file->getRealPath());

        // 1. Transcribe guest voice & auto-detect language with Deepgram Nova-3
        $stt = $this->voiceService->transcribe($binary, $mimeType);
        if (!$stt['ok']) {
            return response()->json([
                'status' => 'fallback',
                'configured' => $stt['configured'],
                'message' => $stt['error'] ?? 'Deepgram STT belum dikonfigurasi.',
            ], 200);
        }

        $transcript = trim((string) ($stt['transcript'] ?? ''));
        $detectedLang = $stt['detected_language'] ?? ($request->input('locale') ?: 'id');

        if ($transcript === '') {
            return response()->json([
                'status' => 'empty',
                'message' => 'Tidak ada suara yang terdeteksi. Silakan coba berbicara lagi.',
                'data' => ['detected_language' => $detectedLang],
            ], 200);
        }

        // 2. Persist conversation & generate warm conversational hotel CS reply in detected language
        $sessionId = $request->input('session_id') ?: ('aegora_' . Str::random(12) . '_' . time());

        $conversation = Conversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'title' => Str::limit($transcript, 40),
                'user_id' => auth()->id() ?? null,
            ]
        );

        if (auth()->check() && !$conversation->user_id) {
            $conversation->user_id = auth()->id();
            $conversation->save();
        }

        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $transcript,
        ]);

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

        $chatLocale = in_array($detectedLang, ['id', 'ms'], true) ? 'id' : ($detectedLang === 'en' ? 'en' : $detectedLang);
        $aiResult = $this->chatService->generateResponse($transcript, $history, $chatLocale, $sessionId, true);

        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResult['content'],
            'facility_data' => $aiResult['facility'] ?? null,
        ]);

        // 3. Synthesize assistant reply with matching Deepgram language voice
        $replyLang = $this->voiceService->detectLanguage($aiResult['content'], $detectedLang);
        $tts = $this->voiceService->synthesize(
            $aiResult['content'],
            $replyLang,
            $request->input('voice')
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'session_id' => $conversation->session_id,
                'transcript' => $transcript,
                'detected_language' => $detectedLang,
                'reply_language' => $replyLang,
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
                'audio' => $tts['ok'] ? [
                    'base64' => base64_encode($tts['audio']),
                    'content_type' => $tts['content_type'] ?? 'audio/mpeg',
                    'model' => $tts['model'],
                    'provider' => 'deepgram',
                ] : null,
                'clean_speech_text' => $tts['clean_text'] ?? $this->voiceService->prepareTextForSpeech($aiResult['content'], $replyLang),
            ],
        ]);
    }
}
