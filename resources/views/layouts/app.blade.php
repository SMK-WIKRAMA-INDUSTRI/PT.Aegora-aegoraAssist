<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Aegora Assist') }} — AI Hotel Concierge</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FAF5E8] text-[#33221C] font-['Plus_Jakarta_Sans',sans-serif] min-h-screen flex flex-col antialiased selection:bg-[#571724] selection:text-white">
    <!-- Top Header Navigation -->
    @include('components.header')

    <!-- Main Container -->
    <main class="flex-1 w-full max-w-5xl mx-auto px-3 sm:px-6 pt-3 pb-32 flex flex-col">
        <!-- Sub-header Concierge Status Bar -->
        @include('components.sub-header')

        <!-- Scrollable Conversation / Content Area -->
        <div id="chat-stream-container" class="space-y-6 mt-4">
            <!-- Welcome Section Card -->
            @include('components.welcome-card')

            <!-- Dynamic Chat Messages Container -->
            <div id="messages-container" class="space-y-5">
                <!-- Initial User Message (Example from Stitch design) -->
                @include('components.chat-bubble', [
                    'role' => 'user',
                    'content' => 'Halo, sarapan besok pagi di restoran buka jam berapa ya? Dan apakah hotel punya fasilitas kolam renang outdoor?',
                    'time' => '14:19 WIB'
                ])

                <!-- Initial AI Response (Example from Stitch design) -->
                @include('components.chat-bubble', [
                    'role' => 'assistant',
                    'time' => '14:19 WIB',
                    'content' => "Halo! Selamat datang di Aegora Hotel 🌿\n\nSarapan pagi di **The Verandah Restaurant** tersedia setiap hari pukul **06.30 – 10.30 WIB** dengan konsep prasmanan masakan autentik Nusantara serta hidangan artisanal internasional segar.\n\nUntuk rekreasi air, kami menyediakan **Outdoor Infinity Pool & Bar di Lantai 3**:",
                    'facility' => [
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
                    ]
                ])
            </div>

            <!-- Loading Indicator (Hidden by default) -->
            <div id="ai-loading-indicator" class="hidden flex items-start gap-3 pt-2">
                <div class="w-8 h-8 rounded-xl overflow-hidden border border-[#571724]/20 shadow-xs shrink-0 bg-[#350711]">
                    <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Assist" class="w-full h-full object-cover">
                </div>
                <div class="bg-[#FBF8F1] border border-[#E9DFCE] rounded-2xl rounded-tl-sm px-5 py-3 shadow-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#571724] animate-bounce" style="animation-delay: 0ms;"></span>
                        <span class="w-2 h-2 rounded-full bg-[#571724] animate-bounce" style="animation-delay: 150ms;"></span>
                        <span class="w-2 h-2 rounded-full bg-[#571724] animate-bounce" style="animation-delay: 300ms;"></span>
                        <span class="text-xs text-stone-500 ml-2 font-medium">Aegora Assist sedang mengetik...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Fixed Input Area & Quick Reply -->
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-gradient-to-t from-[#FAF5E8] via-[#FAF5E8]/95 to-transparent pt-3 pb-3 px-3 sm:px-6">
        <div class="max-w-5xl mx-auto space-y-2.5">
            <!-- Quick Reply / Rekomendasi Pertanyaan -->
            @include('components.quick-reply')

            <!-- Chat Input Bar -->
            @include('components.chat-input')
        </div>
    </div>

    <!-- Contact Staff Modal / Drawer -->
    <div id="staff-modal" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-[#E9DFCE] pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#571724] text-amber-200 flex items-center justify-center font-bold">
                        🏨
                    </div>
                    <div>
                        <h3 class="font-serif text-lg font-bold text-[#3B121C]" data-i18n="modal_staff_title">Hubungi Tim Resepsionis</h3>
                        <p class="text-xs text-stone-500" data-i18n="modal_staff_sub">Front Desk & Concierge Siaga 24/7</p>
                    </div>
                </div>
                <button type="button" id="close-staff-modal" class="text-stone-400 hover:text-stone-700 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <p class="text-sm text-stone-600 leading-relaxed" data-i18n="modal_staff_desc">
                Anda dapat langsung tersambung dengan staf hotel kami melalui saluran cepat berikut:
            </p>

            <div class="space-y-2.5 text-sm">
                <a href="tel:0" class="flex items-center justify-between p-3.5 rounded-xl bg-[#F5EFE2] border border-[#E9DFC8] hover:bg-[#EFE8D6] transition">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">📞</span>
                        <div>
                            <p class="font-semibold text-[#3B121C]" data-i18n="modal_intercom_title">Intercom Kamar</p>
                            <p class="text-xs text-stone-500" data-i18n="modal_intercom_desc">Tekan angka 0 untuk Front Desk</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-[#571724] bg-[#F7E1D7] px-2.5 py-1 rounded-full">Ext. 0</span>
                </a>

                <a href="https://wa.me/6281234567890" target="_blank" class="flex items-center justify-between p-3.5 rounded-xl bg-[#F5EFE2] border border-[#E9DFC8] hover:bg-[#EFE8D6] transition">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">💬</span>
                        <div>
                            <p class="font-semibold text-[#3B121C]">WhatsApp Concierge</p>
                            <p class="text-xs text-stone-500" data-i18n="modal_wa_desc">+62 812-3456-7890 (Respon Cepat)</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-full">WhatsApp</span>
                </a>

                <button type="button" id="request-staff-chat-btn" class="w-full flex items-center justify-between p-3.5 rounded-xl bg-[#571724] text-white hover:bg-[#440F1B] transition shadow-xs">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">🛎️</span>
                        <div class="text-left">
                            <p class="font-semibold" data-i18n="modal_request_title">Minta Panggilan Staf ke Kamar</p>
                            <p class="text-xs text-amber-200/80" data-i18n="modal_request_sub">Kirim notifikasi ke Duty Manager</p>
                        </div>
                    </div>
                    <span class="text-xs font-medium text-amber-200" data-i18n="modal_send_btn">Kirim &rarr;</span>
                </button>
            </div>
        </div>
    </div>
</body>
</html>
