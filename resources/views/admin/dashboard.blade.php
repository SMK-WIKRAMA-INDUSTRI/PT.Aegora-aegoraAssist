<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Operasional Hotel & AI Control — Aegora Assist</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#FAF5E8] text-[#33221C] font-['Plus_Jakarta_Sans',sans-serif] min-h-screen flex flex-col antialiased selection:bg-[#571724] selection:text-white">

    <!-- Top Admin Header -->
    <header class="bg-[#FAF5E8] border-b border-[#EBE1CF] px-4 sm:px-8 py-3 sticky top-0 z-40 backdrop-blur-md bg-[#FAF5E8]/95">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl overflow-hidden bg-[#350711] border border-[#571724]/20 shadow-xs">
                    <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Logo" class="w-full h-full object-cover">
                </div>
                <div>
                    <h1 class="font-serif font-bold text-base sm:text-lg text-[#3A111C]">Hotel Operations & AI Center</h1>
                    <p class="text-[11px] text-stone-500">{{ $hotelName }} • Duty Manager On Duty</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('chat') }}" target="_blank" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#F0E4D3] text-[#571724] border border-[#DFCFA8] hover:bg-[#EAE0CD] transition flex items-center gap-1.5">
                    <span>👁️ Buka Chat Tamu</span>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#571724] text-white hover:bg-[#46111C] transition">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-8 space-y-8">
        
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm rounded-2xl p-4 flex items-center justify-between shadow-xs">
                <span>✓ {{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm rounded-2xl p-4 space-y-1 shadow-xs">
                @foreach ($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('created_guest_credentials'))
            @php $cred = session('created_guest_credentials'); @endphp
            <div class="bg-[#F6EFE0] border-2 border-[#571724]/40 rounded-3xl p-5 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#E5DAC4] pb-2.5">
                    <div>
                        <h3 class="font-serif font-bold text-base text-[#3B121C]">🎟️ Kredensial Akun Tamu Siap Dibagikan</h3>
                        <p class="text-[11px] text-stone-600">Berikan detail login ini kepada tamu agar langsung dapat masuk ke Aegora Assist</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="copyGuestCredentials('{{ addslashes($cred['name']) }}', '{{ addslashes($cred['email']) }}', '{{ addslashes($cred['password']) }}')" class="px-3 py-1.5 rounded-xl bg-[#571724] text-white text-xs font-semibold hover:bg-[#430F1A] transition">
                            📋 Salin Kredensial
                        </button>
                        @if(!empty($cred['phone']))
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $cred['phone']) }}?text={{ urlencode("Halo Bapak/Ibu {$cred['name']}, akun Aegora Assist Hotel Anda telah aktif.\nEmail: {$cred['email']}\nPassword: {$cred['password']}\nLogin di: " . route('login')) }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-emerald-700 text-white text-xs font-semibold hover:bg-emerald-800 transition">
                                💬 Kirim via WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-xl p-3">
                        <span class="text-[10px] uppercase text-stone-500 font-semibold">Nama Tamu</span>
                        <p class="font-bold text-[#3B121C] mt-0.5">{{ $cred['name'] }}</p>
                    </div>
                    <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-xl p-3">
                        <span class="text-[10px] uppercase text-stone-500 font-semibold">Email Login</span>
                        <p class="font-mono font-bold text-[#571724] mt-0.5">{{ $cred['email'] }}</p>
                    </div>
                    <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-xl p-3">
                        <span class="text-[10px] uppercase text-stone-500 font-semibold">Password Sementara</span>
                        <p class="font-mono font-bold text-[#571724] mt-0.5">{{ $cred['password'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Quick Jump Navigation -->
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <a href="#section-guest-register" class="px-3.5 py-2 rounded-xl bg-[#571724] text-white font-semibold hover:bg-[#46111C] transition shadow-2xs">➕ Registrasi Akun Tamu</a>
            <a href="#section-persona-voice" class="px-3.5 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-[#571724] font-semibold hover:bg-[#F3EAD5] transition">🎙️ Suara Deepgram & Persona AI</a>
            <a href="#section-bookings" class="px-3.5 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-[#571724] font-semibold hover:bg-[#F3EAD5] transition">🛎️ Reservasi & Booking</a>
            <a href="#section-knowledge-base" class="px-3.5 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-[#571724] font-semibold hover:bg-[#F3EAD5] transition">🏨 Kelola Kamar & Info Hotel (PRD)</a>
        </div>

        <!-- Quick Operational Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-2xl p-4 shadow-2xs">
                <span class="text-[11px] text-stone-500 font-semibold uppercase">Housekeeping Pending</span>
                <div class="font-serif text-2xl font-bold text-[#571724] mt-1">{{ $stats['pending_housekeeping'] }}</div>
                <p class="text-[10px] text-stone-400 mt-0.5">Tiket fasilitas instan kamar</p>
            </div>
            <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-2xl p-4 shadow-2xs">
                <span class="text-[11px] text-stone-500 font-semibold uppercase">Pesanan F&B & Spa</span>
                <div class="font-serif text-2xl font-bold text-[#571724] mt-1">{{ $stats['active_orders'] }}</div>
                <p class="text-[10px] text-stone-400 mt-0.5">Tagihan masuk folio kamar</p>
            </div>
            <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-2xl p-4 shadow-2xs">
                <span class="text-[11px] text-stone-500 font-semibold uppercase">Formulir Booking Tamu</span>
                <div class="font-serif text-2xl font-bold text-[#571724] mt-1">{{ $stats['pending_bookings'] }}</div>
                <p class="text-[10px] text-stone-400 mt-0.5">Siap dihubungi via WhatsApp</p>
            </div>
            <div class="bg-red-50/70 border border-red-200 rounded-2xl p-4 shadow-2xs">
                <span class="text-[11px] text-red-700 font-bold uppercase flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                    Eskalasi Komplain Darurat
                </span>
                <div class="font-serif text-2xl font-bold text-red-700 mt-1">{{ $stats['urgent_alerts'] }}</div>
                <p class="text-[10px] text-red-500 mt-0.5">Prioritas duty manager</p>
            </div>
        </div>

        <!-- Section: Adaptasi Gaya Bahasa, Kepribadian AI & Karakter Suara Deepgram -->
        <div id="section-persona-voice" class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#EFE5D3] pb-4">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#3B121C] flex items-center gap-2">
                        <span>🎭</span>
                        <span>Pengaturan Gaya Bahasa & Kepribadian AI Concierge</span>
                    </h2>
                    <p class="text-xs text-stone-500">Sesuaikan karakter AI agar ramah, sopan, berwibawa, dan menyesuaikan bahasa tamu</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#F3EAD5] text-[#571724] border border-[#DFCFA8]">
                    <span>Aktif:</span>
                    <strong>
                        @if($currentPersona === 'hospitable_warm') Admin Hotel Ramah & Berwibawa
                        @elseif($currentPersona === 'bali_resort') Bali Resort (Hangat & Santai)
                        @elseif($currentPersona === 'luxury_heritage') Luxury Heritage (Anggun Tradisional)
                        @else Jakarta Business (Formal & Efisien)
                        @endif
                    </strong>
                </span>
            </div>

            <form action="{{ route('admin.persona.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @csrf
                <!-- Option 0: Hospitable Warm (Default Baru sesuai Revisi) -->
                <label class="cursor-pointer border rounded-2xl p-4 transition-all {{ $currentPersona === 'hospitable_warm' ? 'bg-[#F4ECE0] border-[#571724] shadow-xs ring-2 ring-[#571724]/20' : 'bg-[#FAF5E8] border-[#E8DFC9] hover:bg-[#F4ECE0]' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-[#3B121C]">🌟 Admin Hotel Ramah & Berwibawa</span>
                        <input type="radio" name="persona" value="hospitable_warm" {{ $currentPersona === 'hospitable_warm' ? 'checked' : '' }} onchange="this.form.submit()" class="text-[#571724] focus:ring-0">
                    </div>
                    <p class="text-[11px] text-stone-500 mt-2 leading-relaxed">
                        Sopan, hangat, luwes tidak kaku, dan berwibawa layaknya resepsionis hotel bintang lima berpengalaman.
                    </p>
                </label>

                <!-- Option 1: Bali Resort -->
                <label class="cursor-pointer border rounded-2xl p-4 transition-all {{ $currentPersona === 'bali_resort' ? 'bg-[#F4ECE0] border-[#571724] shadow-xs ring-2 ring-[#571724]/20' : 'bg-[#FAF5E8] border-[#E8DFC9] hover:bg-[#F4ECE0]' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-[#3B121C]">🌴 Bali Resort & Boutique</span>
                        <input type="radio" name="persona" value="bali_resort" {{ $currentPersona === 'bali_resort' ? 'checked' : '' }} onchange="this.form.submit()" class="text-[#571724] focus:ring-0">
                    </div>
                    <p class="text-[11px] text-stone-500 mt-2 leading-relaxed">
                        Gaya bahasa ramah, akrab, dan hangat kepulauan ("Halo Kak, selamat datang di...", "Santai sejenak di tepi kolam ya Kak").
                    </p>
                </label>

                <!-- Option 2: Jakarta Business -->
                <label class="cursor-pointer border rounded-2xl p-4 transition-all {{ $currentPersona === 'jakarta_business' ? 'bg-[#F4ECE0] border-[#571724] shadow-xs ring-2 ring-[#571724]/20' : 'bg-[#FAF5E8] border-[#E8DFC9] hover:bg-[#F4ECE0]' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-[#3B121C]">🏢 Jakarta Luxury Business</span>
                        <input type="radio" name="persona" value="jakarta_business" {{ $currentPersona === 'jakarta_business' ? 'checked' : '' }} onchange="this.form.submit()" class="text-[#571724] focus:ring-0">
                    </div>
                    <p class="text-[11px] text-stone-500 mt-2 leading-relaxed">
                        Gaya bahasa modern, tenang, efisien, solutif, dan presisi tinggi khas hotel bisnis bintang lima.
                    </p>
                </label>

                <!-- Option 3: Luxury Heritage -->
                <label class="cursor-pointer border rounded-2xl p-4 transition-all {{ $currentPersona === 'luxury_heritage' ? 'bg-[#F4ECE0] border-[#571724] shadow-xs ring-2 ring-[#571724]/20' : 'bg-[#FAF5E8] border-[#E8DFC9] hover:bg-[#F4ECE0]' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-[#3B121C]">👑 Heritage & High-End Royal</span>
                        <input type="radio" name="persona" value="luxury_heritage" {{ $currentPersona === 'luxury_heritage' ? 'checked' : '' }} onchange="this.form.submit()" class="text-[#571724] focus:ring-0">
                    </div>
                    <p class="text-[11px] text-stone-500 mt-2 leading-relaxed">
                        Gaya bahasa anggun, penuh tata krama nusantara, bertutur kata sangat santun selayaknya menyambut bangsawan.
                    </p>
                </label>
            </form>

            <!-- Deepgram TTS/STS Voice Selector -->
            <div class="pt-4 border-t border-[#EFE5D3] space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="font-serif font-bold text-base text-[#3B121C] flex items-center gap-2">
                            <span>🎙️</span>
                            <span>Konfigurasi Suara AI — Deepgram TTS & STS (Nova-3 + Flux / Aura-2)</span>
                        </h3>
                        <p class="text-[11px] text-stone-500">Suara customer service hotel yang sopan & berwibawa, otomatis menyesuaikan bahasa tamu (tanpa suara Google)</p>
                    </div>
                    @if ($voiceConfig['configured'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Deepgram API Terhubung
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            <span>🔑</span>
                            Isi <code>DEEPGRAM_API_KEY</code> di file <code>.env</code>
                        </span>
                    @endif
                </div>

                <form action="{{ route('admin.voice.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end text-xs">
                    @csrf
                    <div class="md:col-span-5">
                        <label class="block font-bold text-stone-700 mb-1">Suara Bahasa Indonesia (Deepgram Flux TTS)</label>
                        <select name="deepgram_voice_id" class="w-full px-3.5 py-2.5 rounded-xl bg-[#FAF5E8] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                            @foreach ($voiceConfig['voices']['id'] ?? [] as $voiceId => $meta)
                                <option value="{{ $voiceId }}" {{ ($voiceConfig['active_voices']['id'] ?? '') === $voiceId ? 'selected' : '' }}>
                                    {{ $meta['name'] }} — {{ Str::limit($meta['description'], 52) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-5">
                        <label class="block font-bold text-stone-700 mb-1">Suara Bahasa Inggris / Internasional (Deepgram Aura-2)</label>
                        <select name="deepgram_voice_en" class="w-full px-3.5 py-2.5 rounded-xl bg-[#FAF5E8] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                            @foreach ($voiceConfig['voices']['en'] ?? [] as $voiceId => $meta)
                                <option value="{{ $voiceId }}" {{ ($voiceConfig['active_voices']['en'] ?? '') === $voiceId ? 'selected' : '' }}>
                                    {{ $meta['name'] }} — {{ Str::limit($meta['description'], 52) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[#571724] text-white font-bold hover:bg-[#430F1A] transition shadow-xs">
                            Simpan Suara
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section: Fitur Registrasi Akun Tamu oleh Admin (Jika Tamu Belum Punya Akun) -->
        <div id="section-guest-register" class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#EFE5D3] pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">👤</span>
                    <div>
                        <h2 class="font-serif text-lg font-bold text-[#3B121C]">Registrasi & Manajemen Akun Tamu (Oleh Admin)</h2>
                        <p class="text-xs text-stone-500">Daftarkan akun untuk tamu yang belum memiliki akun saat check-in di Front Desk atau dari daftar booking</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-[#F3EAD5] text-[#571724] border border-[#DFCFA8]">
                    Total Tamu Terdaftar: {{ $stats['total_guests'] }}
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Form Daftarkan Tamu Baru -->
                <div class="lg:col-span-5 bg-[#FAF5E8] border border-[#E5DAC4] rounded-2xl p-5 space-y-4">
                    <div>
                        <h3 class="font-serif font-bold text-base text-[#3B121C]">➕ Daftarkan Akun Tamu Baru</h3>
                        <p class="text-[11px] text-stone-500">Booking tamu dengan email/nomor HP yang sama akan otomatis terhubung ke akun ini.</p>
                    </div>

                    <form action="{{ route('admin.guests.store') }}" method="POST" id="admin-register-guest-form" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label for="admin-guest-name" class="block font-bold text-stone-700 mb-1">Nama Lengkap Tamu</label>
                            <input type="text" id="admin-guest-name" name="name" value="{{ old('name') }}" placeholder="Contoh: Ibu Kartika Putri" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                        </div>

                        <div>
                            <label for="admin-guest-phone" class="block font-bold text-stone-700 mb-1">Nomor WhatsApp / HP</label>
                            <input type="tel" id="admin-guest-phone" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 081234567890" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                        </div>

                        <div>
                            <label for="admin-guest-email" class="block font-bold text-stone-700 mb-1">Alamat Email Tamu</label>
                            <input type="email" id="admin-guest-email" name="email" value="{{ old('email') }}" placeholder="tamu@email.com" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="admin-guest-password" class="block font-bold text-stone-700">Kata Sandi Sementara</label>
                                <button type="button" onclick="generateGuestPassword()" class="text-[11px] font-bold text-[#571724] hover:underline">🎲 Buat Sandi Otomatis</button>
                            </div>
                            <div class="relative">
                                <input type="password" id="admin-guest-password" name="password" value="aegora2026" required minlength="6" class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                                <button type="button" onclick="toggleAdminPassword('admin-guest-password', this)" aria-label="Tampilkan atau sembunyikan kata sandi" class="absolute inset-y-0 right-0 px-3 flex items-center text-stone-500 hover:text-[#571724]">
                                    <svg class="w-4 h-4 eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg class="w-4 h-4 eye-closed hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-2.5 rounded-xl bg-[#571724] text-white font-bold hover:bg-[#430F1A] transition shadow-sm">
                            ✓ Daftarkan Akun Tamu Sekarang
                        </button>
                    </form>
                </div>

                <!-- Tabel Daftar Akun Tamu Terdaftar -->
                <div class="lg:col-span-7 space-y-3">
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-xs">
                        <input type="text" name="guest_search" value="{{ $guestSearch }}" placeholder="Cari nama, email, atau nomor HP tamu..." class="flex-1 px-3.5 py-2 rounded-xl bg-[#FAF5E8] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-[#571724] text-white font-semibold hover:bg-[#430F1A] transition">Cari</button>
                        @if($guestSearch)
                            <a href="{{ route('admin.dashboard') }}#section-guest-register" class="px-3 py-2 rounded-xl border border-[#DFCFA8] text-stone-600 hover:bg-[#F3EAD5]">Reset</a>
                        @endif
                    </form>

                    @if ($guestAccounts->isEmpty())
                        <p class="text-xs text-stone-500 italic py-4">Belum ada akun tamu yang cocok.</p>
                    @else
                        <div class="overflow-x-auto max-h-80 overflow-y-auto border border-[#EFE5D3] rounded-2xl bg-[#FAF5E8]">
                            <table class="w-full text-left text-xs">
                                <thead class="sticky top-0 bg-[#F4ECE0] text-stone-700 font-bold border-b border-[#E5DAC4]">
                                    <tr>
                                        <th class="p-2.5">Tamu</th>
                                        <th class="p-2.5">Kontak</th>
                                        <th class="p-2.5">Aktivitas</th>
                                        <th class="p-2.5">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#EFE5D3] text-stone-800">
                                    @foreach ($guestAccounts as $guest)
                                        <tr>
                                            <td class="p-2.5">
                                                <p class="font-bold text-[#3B121C]">{{ $guest->name }}</p>
                                                <p class="text-[10px] text-stone-500">Daftar: {{ $guest->created_at?->format('d M Y') }}</p>
                                            </td>
                                            <td class="p-2.5">
                                                <p class="font-mono text-[11px]">{{ $guest->email }}</p>
                                                <p class="text-[11px] text-stone-500">{{ $guest->phone ?: '-' }}</p>
                                            </td>
                                            <td class="p-2.5 text-[11px] text-stone-600">
                                                <span>💬 {{ $guest->conversations_count }} chat</span><br>
                                                <span>🛎️ {{ $guest->bookings_count }} booking</span>
                                            </td>
                                            <td class="p-2.5">
                                                <div class="flex items-center gap-1.5">
                                                    <form action="{{ route('admin.guests.reset-password', $guest->id) }}" method="POST" onsubmit="return confirm('Reset kata sandi tamu {{ addslashes($guest->name) }} menjadi aegora123?')">
                                                        @csrf
                                                        <input type="hidden" name="new_password" value="aegora123">
                                                        <button type="submit" title="Reset Password ke aegora123" class="px-2 py-1 rounded-lg bg-[#F0E4D3] text-[#571724] font-semibold text-[10px] hover:bg-[#E5D7BF]">Reset Sandi</button>
                                                    </form>
                                                    <form action="{{ route('admin.guests.destroy', $guest->id) }}" method="POST" onsubmit="return confirm('Hapus akun tamu {{ addslashes($guest->name) }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-2 py-1 rounded-lg bg-red-100 text-red-700 font-semibold text-[10px] hover:bg-red-200">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Section: Urgent Escalations Alerts (Sentimen Marah/Komplain Berat) -->
        <div class="bg-red-50/50 border border-red-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-red-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🚨</span>
                    <div>
                        <h2 class="font-serif text-lg font-bold text-red-900">Eskalasi Komplain Darurat (Live Sentiment Analysis)</h2>
                        <p class="text-xs text-red-700">Tamu yang terdeteksi komplain berat otomatis dialihkan ke Duty Manager</p>
                    </div>
                </div>
            </div>

            @if ($escalations->isEmpty())
                <p class="text-xs text-stone-500 italic py-2">Belum ada komplain darurat aktif saat ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-red-200 text-red-900 font-bold">
                                <th class="pb-2">Kode</th>
                                <th class="pb-2">Kamar</th>
                                <th class="pb-2">Keluhan Tamu</th>
                                <th class="pb-2">Waktu</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-100 text-stone-800">
                            @foreach ($escalations as $esc)
                                <tr>
                                    <td class="py-2.5 font-mono font-bold text-red-700">{{ $esc->escalation_code }}</td>
                                    <td class="py-2.5 font-bold">{{ $esc->room_number }}</td>
                                    <td class="py-2.5 max-w-md truncate">{{ $esc->issue_summary }}</td>
                                    <td class="py-2.5 text-stone-500">{{ $esc->created_at->format('H:i') }} WIB</td>
                                    <td class="py-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $esc->status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-200 text-red-800' }}">
                                            {{ strtoupper($esc->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5">
                                        @if($esc->status !== 'resolved')
                                            <form action="{{ route('admin.escalation.status', $esc->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-700 text-white font-semibold text-[11px] hover:bg-emerald-800 transition">
                                                    Selesai Tangani
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-stone-400">Tuntas</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Section: Permintaan Fasilitas Instan (Housekeeping Tickets) -->
        <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-[#EFE5D3] pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🧹</span>
                    <div>
                        <h2 class="font-serif text-lg font-bold text-[#3B121C]">Tiket Permintaan Fasilitas Instan (Housekeeping)</h2>
                        <p class="text-xs text-stone-500">Permintaan handuk, sikat gigi, sabun, dan kebutuhan kamar lainnya yang diketik tamu di chat</p>
                    </div>
                </div>
            </div>

            @if ($housekeepingTickets->isEmpty())
                <p class="text-xs text-stone-500 italic py-2">Belum ada tiket housekeeping hari ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#EFE5D3] text-stone-600 font-bold">
                                <th class="pb-2">Tiket</th>
                                <th class="pb-2">Kamar</th>
                                <th class="pb-2">Rincian Permintaan</th>
                                <th class="pb-2">Waktu</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F2E8D7] text-stone-800">
                            @foreach ($housekeepingTickets as $hk)
                                <tr>
                                    <td class="py-2.5 font-mono font-bold text-[#571724]">{{ $hk->ticket_code }}</td>
                                    <td class="py-2.5 font-bold">{{ $hk->room_number }}</td>
                                    <td class="py-2.5 max-w-md truncate">{{ $hk->items }}</td>
                                    <td class="py-2.5 text-stone-500">{{ $hk->created_at->format('H:i') }} WIB</td>
                                    <td class="py-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $hk->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ strtoupper($hk->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5">
                                        @if($hk->status !== 'completed')
                                            <form action="{{ route('admin.ticket.status', $hk->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-[#571724] text-white font-semibold text-[11px] hover:bg-[#430F1A] transition">
                                                    Antarkan
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-stone-400">Terkirim</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Section: Formulir Booking Tamu (Follow Up WhatsApp & Buat Akun Jika Belum Punya) -->
        <div id="section-bookings" class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-[#EFE5D3] pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛎️</span>
                    <div>
                        <h2 class="font-serif text-lg font-bold text-[#3B121C]">Daftar Formulir Booking Tamu (Lead Reservasi)</h2>
                        <p class="text-xs text-stone-500">Tamu yang telah mengisi form booking di chat untuk difollow-up via WhatsApp atau dibuatkan akun jika belum terdaftar</p>
                    </div>
                </div>
            </div>

            @if ($guestBookings->isEmpty())
                <p class="text-xs text-stone-500 italic py-2">Belum ada data booking baru.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#EFE5D3] text-stone-600 font-bold">
                                <th class="pb-2">Kode Booking</th>
                                <th class="pb-2">Nama Tamu</th>
                                <th class="pb-2">WhatsApp / Telepon</th>
                                <th class="pb-2">Kamar & Total</th>
                                <th class="pb-2">Tanggal</th>
                                <th class="pb-2">Status Akun</th>
                                <th class="pb-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F2E8D7] text-stone-800">
                            @foreach ($guestBookings as $bkg)
                                @php
                                    $hasAccount = $bkg->user_id || ($bkg->guest_email && in_array(strtolower($bkg->guest_email), $registeredEmails, true));
                                @endphp
                                <tr>
                                    <td class="py-2.5 font-mono font-bold text-[#571724]">
                                        {{ $bkg->booking_code }}
                                        @if($bkg->room_number)
                                            <span class="block text-[10px] text-stone-500 font-sans">Kamar {{ $bkg->room_number }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5">
                                        <span class="font-bold">{{ $bkg->guest_name }}</span>
                                        @if($bkg->guest_email)
                                            <span class="block text-[10px] text-stone-500">{{ $bkg->guest_email }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 font-mono">{{ $bkg->guest_phone }}</td>
                                    <td class="py-2.5">
                                        <span>{{ $bkg->room_type }}</span>
                                        @if($bkg->total_amount > 0)
                                            <span class="block text-[10px] font-semibold text-[#571724]">IDR {{ number_format((float) $bkg->total_amount, 0, ',', '.') }} ({{ strtoupper($bkg->payment_status ?? 'unpaid') }})</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-stone-500">{{ $bkg->check_in_date->format('d/m/Y') }} – {{ $bkg->check_out_date->format('d/m/Y') }}</td>
                                    <td class="py-2.5">
                                        @if ($hasAccount)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">✓ Punya Akun</span>
                                        @else
                                            <button type="button" onclick="prefillGuestRegistration('{{ addslashes($bkg->guest_name) }}', '{{ addslashes($bkg->guest_phone) }}', '{{ addslashes($bkg->guest_email ?? '') }}')" class="px-2.5 py-1 rounded-lg bg-amber-100 text-[#571724] border border-amber-300 font-bold text-[10px] hover:bg-amber-200 transition">
                                                + Buatkan Akun
                                            </button>
                                        @endif
                                    </td>
                                    <td class="py-2.5">
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $bkg->guest_phone) }}?text={{ urlencode('Halo Bapak/Ibu ' . $bkg->guest_name . ', kami dari Tim Reservasi ' . $hotelName . ' menindaklanjuti kode booking ' . $bkg->booking_code . '...') }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-emerald-700 text-white font-semibold text-[11px] hover:bg-emerald-800 transition inline-flex items-center gap-1">
                                            <span>💬 Chat WA</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Section: PRD Modul 3 (IN-08) Kelola Tipe Kamar & Knowledge Base Hotel -->
        <div id="section-knowledge-base" class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#EFE5D3] pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🏨</span>
                    <div>
                        <h2 class="font-serif text-lg font-bold text-[#3B121C]">Kelola Data Hotel & Knowledge Base AI (PRD Modul 3)</h2>
                        <p class="text-xs text-stone-500">Perubahan data kamar, fasilitas, jam operasional, kebijakan, & FAQ di sini langsung menjadi sumber jawaban AI Concierge</p>
                    </div>
                </div>
                <span class="text-xs font-semibold text-[#571724] bg-[#F3EAD5] px-3 py-1 rounded-full border border-[#DFCFA8]">
                    {{ $stats['total_rooms'] }} Tipe Kamar • {{ $stats['total_knowledge'] }} Info Hotel
                </span>
            </div>

            <!-- Subsection A: Kelola Tipe Kamar -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-serif font-bold text-base text-[#3B121C]">🛏️ Daftar Tipe Kamar Resmi</h3>
                    <button type="button" onclick="document.getElementById('add-room-form-box').classList.toggle('hidden')" class="px-3 py-1.5 rounded-xl bg-[#571724] text-white text-xs font-semibold hover:bg-[#430F1A] transition">
                        + Tambah Tipe Kamar
                    </button>
                </div>

                <form id="add-room-form-box" action="{{ route('admin.rooms.store') }}" method="POST" class="hidden bg-[#FAF5E8] border border-[#DFCFA8] rounded-2xl p-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Nama Tipe Kamar</label>
                        <input type="text" name="name" required placeholder="Contoh: Premier Garden Room" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Kategori</label>
                        <select name="category" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                            <option value="deluxe">Deluxe</option>
                            <option value="suite">Suite</option>
                            <option value="family">Family</option>
                            <option value="standard">Standard</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Harga / Malam (IDR)</label>
                        <input type="number" name="price_per_night" required min="100000" step="50000" placeholder="1500000" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Kapasitas Tamu</label>
                        <input type="number" name="capacity" required min="1" max="10" value="2" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Luas (m²)</label>
                        <input type="number" name="size_sqm" required min="15" value="38" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Tipe Tempat Tidur</label>
                        <input type="text" name="bed_type" required value="1 King Bed" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Jumlah Unit</label>
                        <input type="number" name="total_units" required min="1" value="8" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Fasilitas (pisahkan koma)</label>
                        <input type="text" name="facilities_text" placeholder="AC, Wi-Fi, Sarapan, Bathtub" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block font-bold text-stone-700 mb-1">Deskripsi Singkat</label>
                        <input type="text" name="description" placeholder="Deskripsi kamar untuk ditampilkan ke tamu..." class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full py-2 rounded-xl bg-[#571724] text-white font-bold hover:bg-[#430F1A]">Simpan Kamar</button>
                    </div>
                </form>

                <div class="overflow-x-auto border border-[#EFE5D3] rounded-2xl bg-[#FAF5E8]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4ECE0] text-stone-700 font-bold border-b border-[#E5DAC4]">
                            <tr>
                                <th class="p-2.5">Tipe Kamar</th>
                                <th class="p-2.5">Harga / Malam</th>
                                <th class="p-2.5">Spesifikasi</th>
                                <th class="p-2.5">Unit</th>
                                <th class="p-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EFE5D3] text-stone-800">
                            @foreach ($rooms as $room)
                                <tr>
                                    <td class="p-2.5">
                                        <p class="font-bold text-[#3B121C]">{{ $room->name }}</p>
                                        <p class="text-[10px] text-stone-500">{{ is_array($room->facilities) ? implode(' • ', array_slice($room->facilities, 0, 4)) : '' }}</p>
                                    </td>
                                    <td class="p-2.5 font-mono font-bold text-[#571724]">IDR {{ number_format((float) $room->price_per_night, 0, ',', '.') }}</td>
                                    <td class="p-2.5 text-stone-600">{{ $room->size_sqm }} m² • Maks {{ $room->capacity }} tamu • {{ $room->bed_type }}</td>
                                    <td class="p-2.5 font-bold">{{ $room->total_units }} unit</td>
                                    <td class="p-2.5">
                                        <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST" onsubmit="return confirm('Hapus tipe kamar {{ addslashes($room->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700 font-semibold text-[10px] hover:bg-red-200">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Subsection B: Kelola Knowledge Base (Fasilitas, Jam, Kebijakan, FAQ) -->
            <div class="space-y-3 pt-4 border-t border-[#EFE5D3]">
                <div class="flex items-center justify-between">
                    <h3 class="font-serif font-bold text-base text-[#3B121C]">📚 Knowledge Base Hotel (Fasilitas, Jam, Kebijakan & FAQ)</h3>
                    <button type="button" onclick="document.getElementById('add-kb-form-box').classList.toggle('hidden')" class="px-3 py-1.5 rounded-xl bg-[#571724] text-white text-xs font-semibold hover:bg-[#430F1A] transition">
                        + Tambah Info Hotel
                    </button>
                </div>

                <form id="add-kb-form-box" action="{{ route('admin.knowledge.store') }}" method="POST" class="hidden bg-[#FAF5E8] border border-[#DFCFA8] rounded-2xl p-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Kategori</label>
                        <select name="category" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                            @foreach ($knowledgeCategories as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Judul Informasi</label>
                        <input type="text" name="title" required placeholder="Contoh: Kids Club Aegora" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Lokasi (Opsional)</label>
                        <input type="text" name="location" placeholder="Contoh: Lantai 3 Sayap Barat" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Jam Operasional (Opsional)</label>
                        <input type="text" name="operating_hours" placeholder="Contoh: 08.00 – 20.00 WIB" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-bold text-stone-700 mb-1">Biaya Tambahan (Opsional)</label>
                        <input type="text" name="extra_fee" placeholder="Contoh: Gratis untuk tamu menginap" class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block font-bold text-stone-700 mb-1">Isi Informasi (Bahasa Indonesia)</label>
                        <textarea name="content" rows="2" required placeholder="Tulis detail resmi yang akan dijawab oleh AI Concierge..." class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]"></textarea>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block font-bold text-stone-700 mb-1">Isi Informasi Bahasa Inggris (Opsional)</label>
                        <textarea name="content_en" rows="2" placeholder="English version for international guests..." class="w-full px-3 py-2 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8]"></textarea>
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#571724] text-white font-bold hover:bg-[#430F1A]">Simpan ke Knowledge Base</button>
                    </div>
                </form>

                <div class="overflow-x-auto max-h-80 overflow-y-auto border border-[#EFE5D3] rounded-2xl bg-[#FAF5E8]">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-[#F4ECE0] text-stone-700 font-bold border-b border-[#E5DAC4]">
                            <tr>
                                <th class="p-2.5">Kategori</th>
                                <th class="p-2.5">Judul & Lokasi / Jam</th>
                                <th class="p-2.5">Ringkasan Konten</th>
                                <th class="p-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EFE5D3] text-stone-800">
                            @foreach ($knowledgeItems as $kb)
                                <tr>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-[#F0E4D3] text-[#571724]">
                                            {{ $knowledgeCategories[$kb->category] ?? $kb->category }}
                                        </span>
                                    </td>
                                    <td class="p-2.5">
                                        <p class="font-bold text-[#3B121C]">{{ $kb->title }}</p>
                                        <p class="text-[10px] text-stone-500">{{ $kb->location }}{{ $kb->location && $kb->operating_hours ? ' • ' : '' }}{{ $kb->operating_hours }}</p>
                                    </td>
                                    <td class="p-2.5 max-w-md text-stone-600">{{ Str::limit($kb->content, 110) }}</td>
                                    <td class="p-2.5">
                                        <form action="{{ route('admin.knowledge.destroy', $kb->id) }}" method="POST" onsubmit="return confirm('Hapus informasi ini dari Knowledge Base?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700 font-semibold text-[10px] hover:bg-red-200">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <script>
        function toggleAdminPassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-open')?.classList.toggle('hidden', isHidden);
            btn.querySelector('.eye-closed')?.classList.toggle('hidden', !isHidden);
        }

        function generateGuestPassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
            let pass = 'Aegora-';
            for (let i = 0; i < 4; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            const input = document.getElementById('admin-guest-password');
            if (input) {
                input.value = pass;
                input.type = 'text';
            }
        }

        function prefillGuestRegistration(name, phone, email) {
            const nameEl = document.getElementById('admin-guest-name');
            const phoneEl = document.getElementById('admin-guest-phone');
            const emailEl = document.getElementById('admin-guest-email');
            if (nameEl) nameEl.value = name || '';
            if (phoneEl) phoneEl.value = phone || '';
            if (emailEl) emailEl.value = email || '';
            const section = document.getElementById('section-guest-register');
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (emailEl && !email) {
                emailEl.focus();
            } else if (nameEl) {
                nameEl.focus();
            }
        }

        function copyGuestCredentials(name, email, password) {
            const text = `Akun Aegora Assist — ${name}\nEmail: ${email}\nPassword: ${password}`;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Kredensial login tamu berhasil disalin!');
                });
            } else {
                prompt('Salin kredensial login tamu:', text);
            }
        }
    </script>

</body>
</html>
