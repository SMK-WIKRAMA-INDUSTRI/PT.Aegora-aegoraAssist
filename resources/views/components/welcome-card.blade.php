<div class="w-full bg-[#FAF5E8] border border-[#E8DFC9] rounded-3xl p-5 sm:p-7 shadow-xs space-y-6">
    <!-- Top Branding & Greeting -->
    <div class="flex items-start gap-4">
        <div class="w-14 h-14 rounded-2xl overflow-hidden border border-[#571724]/20 shadow-md shrink-0 bg-[#350711]">
            <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Assist Emblem" class="w-full h-full object-cover">
        </div>
        <div class="space-y-1">
            <span class="inline-block text-[10px] font-bold tracking-widest text-[#7C2436] uppercase" data-i18n="welcome_tag">
                LAYANAN KERAMAHAN DIGITAL
            </span>
            <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#3B121C] tracking-tight" data-i18n="welcome_title">
                Selamat Datang di Aegora Hotel
            </h2>
        </div>
    </div>

    <!-- Description -->
    <p class="text-xs sm:text-sm text-stone-600 leading-relaxed max-w-3xl" data-i18n="welcome_desc">
        Saya <strong class="text-[#3B121C] font-semibold">Aegora Assist</strong>, asisten virtual pribadi Anda. Ada yang bisa saya bantu terkait reservasi kamar, jadwal sarapan, fasilitas spa, atau panduan menginap hari ini?
    </p>

    <!-- 4 Category Service Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-1">
        @include('components.service-card', [
            'icon' => 'bed',
            'key' => 'room',
            'title' => 'Pesan & Cek Kamar',
            'description' => 'Ketersediaan, tipe Deluxe/Executive, tarif malam',
            'prompt' => 'Apakah tersedia kamar Deluxe atau Executive untuk malam ini?'
        ])

        @include('components.service-card', [
            'icon' => 'pool',
            'key' => 'facility',
            'title' => 'Fasilitas & Jadwal',
            'description' => 'The Verandah, Infinity Pool Lt. 3, Spa, Gym',
            'prompt' => 'Jam berapa sarapan dibuka dan apa saja fasilitas yang tersedia di hotel?'
        ])

        @include('components.service-card', [
            'icon' => 'bill',
            'key' => 'checkin',
            'title' => 'Check-in & Tagihan',
            'description' => 'Panduan 14.00 WIB, deposit, rincian e-folio',
            'prompt' => 'Bagaimana prosedur check-in, deposit, dan rincian e-folio tagihan?'
        ])

        @include('components.service-card', [
            'icon' => 'staff',
            'key' => 'staff',
            'title' => 'Hubungi Staf Langsung',
            'description' => 'Sambungkan ke Resepsionis & Concierge hotel',
            'prompt' => 'Bagaimana cara menghubungi resepsionis hotel secara langsung?'
        ])
    </div>
</div>
