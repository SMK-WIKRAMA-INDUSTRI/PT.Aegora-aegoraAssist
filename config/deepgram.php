<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deepgram Voice AI Configuration (STT Nova-3 + TTS Flux & Aura-2)
    |--------------------------------------------------------------------------
    |
    | Cukup masukkan DEEPGRAM_API_KEY pada file .env.
    | Sistem akan otomatis menyesuaikan karakter suara dan bahasa pengguna
    | dengan nada customer service hotel yang ramah, sopan, dan berwibawa.
    |
    */

    'api_key' => env('DEEPGRAM_API_KEY', ''),

    'base_url' => rtrim(env('DEEPGRAM_BASE_URL', 'https://api.deepgram.com'), '/'),

    'timeout' => (int) env('DEEPGRAM_TIMEOUT', 25),

    // Speech-to-Text (STT) Configuration
    'stt' => [
        'model' => env('DEEPGRAM_STT_MODEL', 'nova-3'),
        'detect_language' => true,
        'smart_format' => true,
        'punctuate' => true,
    ],

    // Text-to-Speech (TTS) Configuration
    'tts' => [
        'default_id_voice' => env('DEEPGRAM_TTS_ID_VOICE', 'flux-fitri-id'),
        'default_en_voice' => env('DEEPGRAM_TTS_EN_VOICE', 'aura-2-helena-en'),
        'speed' => (float) env('DEEPGRAM_TTS_SPEED', 1.0),
        'expressivity' => (int) env('DEEPGRAM_TTS_EXPRESSIVITY', 0),
        'encoding' => 'mp3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Karakter Suara Customer Service Hotel (Deepgram Catalog)
    |--------------------------------------------------------------------------
    |
    | Pilihan karakter suara ramah, sopan, dan berwibawa untuk Front Office
    | & AI Concierge Aegora Hotel.
    |
    */
    'voices' => [
        'id' => [
            'flux-fitri-id' => [
                'name' => 'Fitri (Concierge Wanita)',
                'description' => 'Lembut, ramah, sopan, dan mudah didekati — ideal untuk resepsionis hotel bintang lima',
                'gender' => 'female',
                'engine' => 'v2',
                'lang' => 'id',
            ],
            'flux-dewi-id' => [
                'name' => 'Dewi (Guest Relations)',
                'description' => 'Jernih, sopan, ceria, dan profesional dalam melayani tamu',
                'gender' => 'female',
                'engine' => 'v2',
                'lang' => 'id',
            ],
            'flux-teguh-id' => [
                'name' => 'Teguh (Duty Manager Pria)',
                'description' => 'Tenang, dalam, sopan, dan sangat berwibawa',
                'gender' => 'male',
                'engine' => 'v2',
                'lang' => 'id',
            ],
            'flux-fajar-id' => [
                'name' => 'Fajar (Concierge Pria)',
                'description' => 'Sabar, tenang, jernih, dan hangat dalam membantu tamu',
                'gender' => 'male',
                'engine' => 'v2',
                'lang' => 'id',
            ],
            'flux-rizky-id' => [
                'name' => 'Rizky (Hospitality Host)',
                'description' => 'Tenang, komunikatif, hangat, dan tidak kaku',
                'gender' => 'male',
                'engine' => 'v2',
                'lang' => 'id',
            ],
        ],
        'en' => [
            'aura-2-helena-en' => [
                'name' => 'Helena (Warm Hospitality)',
                'description' => 'Caring, natural, polite, and reassuring hotel concierge voice',
                'gender' => 'female',
                'engine' => 'v1',
                'lang' => 'en',
            ],
            'aura-2-thalia-en' => [
                'name' => 'Thalia (Poised Customer Service)',
                'description' => 'Clear, confident, poised, and authoritative customer service voice',
                'gender' => 'female',
                'engine' => 'v1',
                'lang' => 'en',
            ],
            'aura-2-cordelia-en' => [
                'name' => 'Cordelia (Friendly Concierge)',
                'description' => 'Approachable, warm, and genuinely polite guest relations tone',
                'gender' => 'female',
                'engine' => 'v1',
                'lang' => 'en',
            ],
            'aura-2-apollo-en' => [
                'name' => 'Apollo (Executive Duty Manager)',
                'description' => 'Confident, calm, comfortable, and authoritative male voice',
                'gender' => 'male',
                'engine' => 'v1',
                'lang' => 'en',
            ],
        ],
    ],

    // Pemetaan otomatis untuk bahasa internasional lain saat tamu berbicara bahasa asing
    'multilingual_map' => [
        'ja' => ['model' => 'aura-2-fujin-ja', 'engine' => 'v1', 'label' => 'Japanese (Aura-2 Fujin)'],
        'fr' => ['model' => 'aura-2-agathe-fr', 'engine' => 'v1', 'label' => 'French (Aura-2 Agathe)'],
        'de' => ['model' => 'aura-2-viktoria-de', 'engine' => 'v1', 'label' => 'German (Aura-2 Viktoria)'],
        'es' => ['model' => 'aura-2-selena-es', 'engine' => 'v1', 'label' => 'Spanish (Aura-2 Selena)'],
        'it' => ['model' => 'aura-2-helena-en', 'engine' => 'v1', 'label' => 'Italian / Multilingual'],
        'nl' => ['model' => 'aura-2-helena-en', 'engine' => 'v1', 'label' => 'Dutch / Multilingual'],
    ],
];
