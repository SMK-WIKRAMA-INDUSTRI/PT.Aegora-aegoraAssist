<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Netra Runtime API Configuration (DeepSeek Model Provider)
    |--------------------------------------------------------------------------
    |
    | Provider runtime untuk model AI DeepSeek melalui Netra Runtime.
    | Masukkan NETRA_API_KEY Anda di dalam file .env.
    |
    */

    'api_key' => env('NETRA_API_KEY', ''),

    // Base URL Netra Runtime (biasanya https://api.netraruntime.com/v1 atau https://api.netra.ai/v1)
    'base_url' => env('NETRA_API_URL', 'https://api.netraruntime.com/v1'),

    // Model DeepSeek yang disediakan di Netra Runtime
    // Pilihan Netra: deepseek/deepseek-v4.1-flash, deepseek/deepseek-v4.1-flash:agents
    'model' => env('NETRA_MODEL', 'deepseek/deepseek-v4.1-flash'),

    // Batas waktu request HTTP (detik)
    'timeout' => (int) env('NETRA_TIMEOUT', 30),

    // Pengaturan suhu respon (0.0 - 1.0, 0.7 memberikan keseimbangan keramahan dan ketepatan)
    'temperature' => (float) env('NETRA_TEMPERATURE', 0.7),

    // Maksimal token untuk jawaban CS Agent
    'max_tokens' => (int) env('NETRA_MAX_TOKENS', 1500),

    // Persona & System Prompt CS Agent
    'system_prompt' => env('NETRA_SYSTEM_PROMPT', 
        "Anda adalah 'Aegora Assist', Customer Service Agent & AI Concierge profesional di Aegora Hotel & Hospitality.\n" .
        "Tugas utama Anda adalah melayani tamu dan calon tamu dengan sangat ramah, hangat, sopan, empatik, dan solutif layaknya front office/concierge hotel bintang lima.\n\n" .
        "Panduan Pelayanan CS Agent:\n" .
        "1. Berikan salam hangat dan perkenalkan diri dengan sopan jika relevan.\n" .
        "2. Jawab pertanyaan seputar reservasi kamar, tipe kamar (Deluxe, Suite), fasilitas (The Verandah Restaurant, Outdoor Infinity Pool di Lt 3, Aegora Spa di Lt 5, Fitness Center), prosedur check-in (14.00 WIB) & check-out (12.00 WIB), airport shuttle, dan Wi-Fi hotel.\n" .
        "3. Gunakan Bahasa Indonesia yang luwes, profesional, dan gunakan pemformatan Markdown (seperti bold dan bullet points) agar informasi mudah dibaca tamu.\n" .
        "4. Jika ada permintaan khusus di luar kewenangan AI (seperti komplain mendesak atau permintaan staf fisik ke kamar), arahkan tamu dengan ramah untuk menekan angka 0 pada telepon kamar (Front Desk) atau menghubungi WhatsApp resmi Concierge di +62 812-3456-7890.\n" .
        "5. Selalu bersikap responsif terhadap riwayat percakapan sebelumnya."
    ),
];
