<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $hotelName }} — AI Concierge & Smart Hospitality</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#FAF5E8] text-[#33221C] font-['Plus_Jakarta_Sans',sans-serif] min-h-screen flex flex-col antialiased selection:bg-[#571724] selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="w-full bg-[#FAF5E8]/90 backdrop-blur-md border-b border-[#EBE1CF] sticky top-0 z-50 px-4 sm:px-8 py-3.5">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl overflow-hidden border border-[#571724]/20 shadow-xs shrink-0 bg-[#350711] flex items-center justify-center">
                    <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Assist Logo" class="w-full h-full object-cover">
                </div>
                <div>
                    <h1 class="font-serif font-bold text-lg text-[#3A111C] tracking-tight group-hover:text-[#571724] transition">Aegora Assist</h1>
                    <p class="text-[10px] text-stone-500 uppercase tracking-widest font-semibold">Luxury AI Hospitality</p>
                </div>
            </a>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-7 text-xs font-semibold text-stone-700">
                <a href="#fitur" class="hover:text-[#571724] transition">Fitur Unggulan</a>
                <a href="#kamar" class="hover:text-[#571724] transition">Koleksi Kamar</a>
                <a href="#fasilitas" class="hover:text-[#571724] transition">Fasilitas & Dining</a>
                <a href="#integrasi" class="hover:text-[#571724] transition">Integrasi PMS</a>
            </nav>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ route('profile') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#571724] bg-[#F7EFE2] border border-[#DFCFA8] hover:bg-[#EFE3CF] transition">
                        <span>👤 {{ Str::limit(Auth::user()->name, 14) }}</span>
                    </a>
                    @if (Auth::user()->isStaff())
                        <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline-block px-3 py-2 rounded-xl text-xs font-semibold text-[#571724] border border-[#DFCFA8] hover:bg-[#F3EAD7] transition">
                            Dashboard Staf
                        </a>
                    @endif
                    <a href="{{ route('chat') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-[#571724] text-white hover:bg-[#46111C] shadow-md hover:shadow-lg transition active:scale-95">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Concierge Chat</span>
                    </a>
                @else
                    <a href="{{ route('register') }}" class="hidden sm:inline-block px-3.5 py-2 rounded-xl text-xs font-semibold text-[#571724] border border-[#DFCFA8] hover:bg-[#F3EAD7] transition">
                        Daftar Tamu
                    </a>
                    <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-[#571724] border border-[#DFCFA8] hover:bg-[#F3EAD7] transition">
                        Masuk
                    </a>
                    <a href="{{ route('login', ['role' => 'staff']) }}" class="hidden lg:inline-block px-3 py-2 rounded-xl text-xs font-medium text-stone-600 hover:text-stone-900 transition">
                        Portal Staff
                    </a>
                    <a href="{{ route('chat') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-[#571724] text-white hover:bg-[#46111C] shadow-md hover:shadow-lg transition active:scale-95">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Coba Concierge Live</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-20 px-4 sm:px-8 overflow-hidden">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Hero Text -->
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#F5EAD4] border border-[#DFCFA8] text-[#571724] text-xs font-bold tracking-wide">
                    <span class="text-amber-700">✦</span>
                    <span>AI HOTEL CONCIERGE & CS AGENT GENERASI TERBARU</span>
                </div>

                <h1 class="font-serif text-3xl sm:text-5xl lg:text-6xl font-bold text-[#3B121C] leading-[1.15] tracking-tight">
                    Keramahan Bintang Lima, <br class="hidden sm:inline">
                    <span class="italic font-normal text-[#6E1C2E]">Terhubung Cerdas 24/7.</span>
                </h1>

                <p class="text-sm sm:text-base text-stone-600 leading-relaxed max-w-2xl">
                    Tingkatkan pengalaman menginap tamu Anda dengan asisten virtual pribadi. Menerima pesanan F&B langsung masuk tagihan kamar, meneruskan tiket fasilitas housekeeping instan, menangani eskalasi komplain secara empatik, dan meningkatkan omzet dengan <em>proactive upselling</em>.
                </p>

                <!-- CTA Button Row -->
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="{{ route('chat') }}" class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl bg-[#571724] text-white hover:bg-[#430F1A] font-semibold text-sm shadow-lg hover:shadow-xl transition active:scale-95">
                        <span>💬 Buka Percakapan Tamu</span>
                        <span class="text-amber-300">→</span>
                    </a>
                    <a href="#fitur" class="inline-flex items-center gap-2 px-5 py-3.5 rounded-2xl bg-[#FAF5E8] border border-[#DFCFA8] hover:bg-[#F2E8D3] font-semibold text-sm text-[#4A1521] transition">
                        <span>Pelajari Fitur Unik</span>
                    </a>
                </div>

                <!-- Metrics Highlights -->
                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-[#E8DFC9]">
                    <div>
                        <div class="font-serif text-2xl sm:text-3xl font-bold text-[#571724]">&lt; 2 Detik</div>
                        <p class="text-xs text-stone-500 mt-0.5">Respon AI Instan</p>
                    </div>
                    <div>
                        <div class="font-serif text-2xl sm:text-3xl font-bold text-[#571724]">100%</div>
                        <p class="text-xs text-stone-500 mt-0.5">Tiket Terdistribusi ke Staff</p>
                    </div>
                    <div>
                        <div class="font-serif text-2xl sm:text-3xl font-bold text-[#571724]">+38%</div>
                        <p class="text-xs text-stone-500 mt-0.5">Upselling F&B & Spa</p>
                    </div>
                </div>
            </div>

            <!-- Right Interactive Demo Card -->
            <div class="lg:col-span-5 relative">
                <div class="relative bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4">
                    <!-- Card Top Header -->
                    <div class="flex items-center justify-between border-b border-[#EFE5D3] pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl overflow-hidden bg-[#350711]">
                                <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h3 class="font-bold text-xs text-[#3B121C]">Simulasi AI Concierge</h3>
                                <p class="text-[10px] text-emerald-700 font-semibold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siaga Melayani
                                </p>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-[#F2E8D4] text-[#571724] font-bold">Lantai 304</span>
                    </div>

                    <!-- Chat Snippets -->
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-end">
                            <div class="bg-[#F4ECE0] border border-[#DFCFA8] rounded-2xl rounded-tr-xs px-4 py-2.5 max-w-[85%] text-stone-800">
                                Minta 2 handuk tambahan dan sikat gigi ke kamar 304 ya.
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-lg overflow-hidden bg-[#350711] shrink-0 mt-0.5">
                                <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora" class="w-full h-full object-cover">
                            </div>
                            <div class="bg-[#FAF5E8] border border-[#E8DFC9] rounded-2xl rounded-tl-xs p-3.5 space-y-2 text-stone-700 shadow-2xs">
                                <div class="flex items-center justify-between text-[10px] font-bold text-[#571724]">
                                    <span>🧹 TIKET HOUSEKEEPING #HK-9821</span>
                                    <span class="text-emerald-700 font-semibold">Terkirim ke Staff</span>
                                </div>
                                <p class="text-[11px] leading-relaxed">
                                    Permintaan Anda telah masuk ke sistem. Petugas <em>room attendant</em> akan mengantarkan ke <strong>Kamar 304</strong> dalam estimasi <strong>10–15 menit</strong>.
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <div class="bg-[#F4ECE0] border border-[#DFCFA8] rounded-2xl rounded-tr-xs px-4 py-2.5 max-w-[85%] text-stone-800">
                                Besok ulang tahun istri saya, ada rekomendasi?
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-lg overflow-hidden bg-[#350711] shrink-0 mt-0.5">
                                <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora" class="w-full h-full object-cover">
                            </div>
                            <div class="bg-[#FAF5E8] border border-[#E8DFC9] rounded-2xl rounded-tl-xs p-3.5 space-y-2 text-stone-700 shadow-2xs">
                                <div class="text-[10px] font-bold text-amber-800 flex items-center gap-1">
                                    <span>✨ PROACTIVE UPSELLING</span>
                                </div>
                                <p class="text-[11px] leading-relaxed">
                                    Selamat ulang tahun untuk istri Anda! 🎉 Kami memiliki <strong>Paket Dekorasi Mawar Romantis & Artisanal Cake</strong> (Rp350.000) atau <strong>Upgrade Executive Club Suite (Diskon 30%)</strong>. Mau kami siapkan kejutan ini?
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom CTA to Chat -->
                    <div class="pt-2">
                        <a href="{{ route('chat') }}" class="w-full block text-center py-2.5 rounded-xl bg-[#571724] text-white text-xs font-bold hover:bg-[#440F1B] transition shadow-xs">
                            Buka Percakapan Lengkap Tamu &rarr;
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Fitur Unik Section -->
    <section id="fitur" class="py-16 bg-[#F6F0E2] border-y border-[#E8DEC7] px-4 sm:px-8">
        <div class="max-w-7xl mx-auto space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-[11px] font-bold uppercase tracking-widest text-[#7C2436]">KEUNGGULAN KOMPETITIF</span>
                <h2 class="font-serif text-3xl sm:text-4xl font-bold text-[#3B121C]">
                    6 Fitur Khusus Berbasis Kebutuhan Industri Perhotelan
                </h2>
                <p class="text-xs sm:text-sm text-stone-600 leading-relaxed">
                    Dirancang bukan sekadar bot FAQ umum, melainkan asisten operasional hotel yang terintegrasi langsung dengan PMS dan staf hotel.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="bg-[#FAF5E8] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-3 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-[#571724] text-amber-200 flex items-center justify-center font-bold text-xl">
                        🛎️
                    </div>
                    <h3 class="font-serif font-bold text-lg text-[#3B121C]">Permintaan Fasilitas Instan</h3>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Tamu cukup mengetik <em>"Minta 2 handuk dan sikat gigi ke kamar 304"</em>, chatbot otomatis mencatat nomor kamar dan meneruskan tiket ke dashboard staf housekeeping.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-[#FAF5E8] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-3 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-[#571724] text-amber-200 flex items-center justify-center font-bold text-xl">
                        🍽️
                    </div>
                    <h3 class="font-serif font-bold text-lg text-[#3B121C]">Pemesanan F&B & Spa (Folio Billing)</h3>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Tamu memesan hidangan restoran The Verandah atau reservasi sesi Aegora Spa langsung di chat, dengan tagihan otomatis dicatat ke tagihan kamar (folio) saat check-out.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-[#FAF5E8] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-3 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-[#571724] text-amber-200 flex items-center justify-center font-bold text-xl">
                        🎭
                    </div>
                    <h3 class="font-serif font-bold text-lg text-[#3B121C]">Adaptasi Gaya Bahasa (Persona)</h3>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Pemilik hotel dapat memilih kepribadian AI: gaya santai & hangat Bali Resort ("Halo Kak...") atau formal & ringkas Jakarta Luxury Business dari panel kontrol.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-[#FAF5E8] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-3 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-[#571724] text-amber-200 flex items-center justify-center font-bold text-xl">
                        🚨
                    </div>
                    <h3 class="font-serif font-bold text-lg text-[#3B121C]">Deteksi Emosi & Komplain Darurat</h3>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Jika mendeteksi komplain berat ("AC bocor dan berisik"), AI berhenti dari template biasa, meminta maaf empatik, dan otomatis mengeskalasikan ke Duty Manager.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-[#FAF5E8] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-3 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-[#571724] text-amber-200 flex items-center justify-center font-bold text-xl">
                        💎
                    </div>
                    <h3 class="font-serif font-bold text-lg text-[#3B121C]">Proactive Upselling</h3>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Mendeteksi momen perayaan ulang tahun atau honeymoon untuk menawarkan dekorasi bunga mawar atau upgrade ke Executive Suite dengan harga khusus.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-[#FAF5E8] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-3 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-[#571724] text-amber-200 flex items-center justify-center font-bold text-xl">
                        📑
                    </div>
                    <h3 class="font-serif font-bold text-lg text-[#3B121C]">Form Booking & Upload PDF</h3>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Menyediakan formulir pemesanan kamar yang rapi di dalam obrolan, menerbitkan bukti booking, dan mendukung upload file PDF tiket/voucher tamu.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Room Collection Section -->
    <section id="kamar" class="py-16 px-4 sm:px-8 max-w-7xl mx-auto space-y-10">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-widest text-[#7C2436]">AKOMODASI TERBAIK</span>
                <h2 class="font-serif text-3xl sm:text-4xl font-bold text-[#3B121C]">Pilihan Tipe Kamar & Suite</h2>
            </div>
            <a href="{{ route('chat') }}" class="text-xs font-bold text-[#571724] hover:underline flex items-center gap-1">
                <span>Tanya Ketersediaan di Chat</span> &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Room 1 -->
            <div class="bg-[#FBF8F1] border border-[#E7DDC5] rounded-3xl overflow-hidden shadow-xs hover:shadow-md transition">
                <div class="h-64 bg-stone-800 relative overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=800&q=80" alt="Deluxe Room" class="w-full h-full object-cover opacity-90 hover:scale-105 transition duration-500">
                    <span class="absolute top-4 right-4 bg-[#571724] text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm">
                        Mulai IDR 1.250.000++ / malam
                    </span>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <h3 class="font-serif text-xl font-bold text-[#3B121C]">Deluxe King / Twin Room (36 m²)</h3>
                        <p class="text-xs text-stone-500 mt-1">Pemandangan Kota • Tempat Tidur King Koil • Walk-in Rain Shower</p>
                    </div>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Dirancang untuk kenyamanan istirahat maksimal, dilengkapi Smart TV 55", koneksi Wi-Fi berkecepatan tinggi, dan complimentary amenities artisanal.
                    </p>
                    <a href="{{ route('chat') }}" class="block text-center py-2.5 rounded-xl bg-[#571724] text-white text-xs font-semibold hover:bg-[#450F1B] transition">
                        Pesan Kamar Ini via Chat
                    </a>
                </div>
            </div>

            <!-- Room 2 -->
            <div class="bg-[#FBF8F1] border border-[#E7DDC5] rounded-3xl overflow-hidden shadow-xs hover:shadow-md transition">
                <div class="h-64 bg-stone-800 relative overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1591088398332-8a7791972843?auto=format&fit=crop&w=800&q=80" alt="Executive Club Suite" class="w-full h-full object-cover opacity-90 hover:scale-105 transition duration-500">
                    <span class="absolute top-4 right-4 bg-[#571724] text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm">
                        Mulai IDR 2.450.000++ / malam
                    </span>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <h3 class="font-serif text-xl font-bold text-[#3B121C]">Executive Club Suite (58 m²)</h3>
                        <p class="text-xs text-stone-500 mt-1">Akses Club Lounge Lt. 21 • Bathtub Marmer • Mesin Kopi Nespresso</p>
                    </div>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Kemewahan tanpa kompromi dengan sajian Evening Cocktails & Canapés gratis di Club Lounge, ruang tamu terpisah, dan layanan personal concierge prioritas.
                    </p>
                    <a href="{{ route('chat') }}" class="block text-center py-2.5 rounded-xl bg-[#571724] text-white text-xs font-semibold hover:bg-[#450F1B] transition">
                        Pesan Suite Ini via Chat
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="mt-auto bg-[#310A14] text-stone-300 py-12 px-4 sm:px-8 border-t border-[#4E1422]">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl overflow-hidden bg-[#571724] border border-amber-300/30">
                    <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora" class="w-full h-full object-cover">
                </div>
                <div>
                    <h4 class="font-serif font-bold text-white text-base">Aegora Assist</h4>
                    <p class="text-[11px] text-stone-400">Platform AI Hospitality & Concierge Resmi PT Aegora</p>
                </div>
            </div>

            <div class="flex items-center gap-6 text-xs text-stone-300">
                <a href="{{ route('chat') }}" class="hover:text-white transition">Live Concierge</a>
                <a href="{{ route('login') }}" class="hover:text-white transition">Login Portal Staff</a>
                <a href="https://wa.me/6281234567890" target="_blank" class="hover:text-white transition">WhatsApp Support</a>
            </div>

            <p class="text-[11px] text-stone-400">
                &copy; {{ date('Y') }} Aegora Hospitality Solutions. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>
