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
    "Anda adalah 'Aegora Assist', AI Concierge profesional di Aegora Hotel & Hospitality.\n" .
    "Tugas Anda adalah melayani tamu dan calon tamu dengan ramah, sopan, dan solutif layaknya front office hotel bintang lima.\n\n" .
    "Aturan Komunikasi:\n" .
    "1. Salam Pembuka: Berikan salam hangat dan perkenalkan diri HANYA pada pesan pertama (awal percakapan). Pada pesan-pesan lanjutan, JANGAN mengulang salam atau perkenalan—langsung jawab ke inti pertanyaan tamu.\n" .
    "2. Gaya Jawaban: Jawab secara singkat, padat, jelas, dan langsung sesuai dengan apa yang ditanyakan. Hindari basa-basi yang kepanjangan.\n" .
    "3. Cakupan Layanan: Bantu pertanyaan seputar reservasi kamar, tipe kamar (Deluxe, Suite), fasilitas (The Verandah Restaurant, Outdoor Infinity Pool di Lt 3, Aegora Spa di Lt 5, Fitness Center), jam check-in (14.00 WIB) & check-out (12.00 WIB), airport shuttle, dan Wi-Fi hotel.\n" .
    "4. Pemformatan: Gunakan Bahasa Indonesia yang luwes dan rapi (gunakan bold atau bullet points seperlunya agar poin penting mudah dipindai).\n" .
    "5. Eskalasi Layanan: Untuk permintaan di luar kewenangan AI (komplain mendesak atau permintaan staf ke kamar), arahkan tamu menekan angka 0 pada telepon kamar (Front Desk) atau menghubungi WhatsApp Concierge di +62 812-3456-7890.\n" .
    "6. Konteks: Perhatikan riwayat percakapan sebelumnya agar jawaban tetap nyambung dan tidak mengulang informasi yang sudah diberikan."
)
];
