<div class="w-full bg-[#FAF5E8] border border-[#E8DFC9] rounded-3xl p-5 sm:p-7 shadow-xs space-y-6">
    <!-- Top Branding & Greeting -->
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-[#571724] text-amber-200 flex items-center justify-center shadow-md shrink-0">
            <!-- Elegant Emblem -->
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <div class="space-y-1">
            <span class="inline-block text-[10px] font-bold tracking-widest text-[#7C2436] uppercase">
                LAYANAN KERAMAHAN DIGITAL
            </span>
            <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#3B121C] tracking-tight">
                Selamat Datang di Aegora Hotel
            </h2>
        </div>
    </div>

    <!-- Description -->
    <p class="text-xs sm:text-sm text-stone-600 leading-relaxed max-w-3xl">
        Saya <strong class="text-[#3B121C] font-semibold">Aegora Assist</strong>, asisten virtual pribadi Anda. Ada yang bisa saya bantu terkait reservasi kamar, jadwal sarapan, fasilitas spa, atau panduan menginap hari ini?
    </p>

    <!-- 4 Category Service Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-1">
        @include('components.service-card', [
            'icon' => 'bed',
            'title' => 'Pesan & Cek Kamar',
            'description' => 'Ketersediaan, tipe Deluxe/Executive, tarif malam',
            'prompt' => 'Apakah tersedia kamar Deluxe atau Executive untuk malam ini?'
        ])

        @include('components.service-card', [
            'icon' => 'pool',
            'title' => 'Fasilitas & Jadwal',
            'description' => 'The Verandah, Infinity Pool Lt. 3, Spa, Gym',
            'prompt' => 'Jam berapa sarapan dibuka dan apa saja fasilitas yang tersedia di hotel?'
        ])

        @include('components.service-card', [
            'icon' => 'bill',
            'title' => 'Check-in & Tagihan',
            'description' => 'Panduan 14.00 WIB, deposit, rincian e-folio',
            'prompt' => 'Bagaimana prosedur check-in, deposit, dan rincian e-folio tagihan?'
        ])

        @include('components.service-card', [
            'icon' => 'staff',
            'title' => 'Hubungi Staf Langsung',
            'description' => 'Sambungkan ke Resepsionis & Concierge hotel',
            'prompt' => 'Bagaimana cara menghubungi resepsionis hotel secara langsung?'
        ])
    </div>
</div>
