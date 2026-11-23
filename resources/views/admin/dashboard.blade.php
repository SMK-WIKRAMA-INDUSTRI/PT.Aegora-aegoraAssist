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

        <!-- Section: Adaptasi Gaya Bahasa & Kepribadian AI -->
        <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#EFE5D3] pb-4">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#3B121C] flex items-center gap-2">
                        <span>🎭</span>
                        <span>Pengaturan Gaya Bahasa & Kepribadian AI Concierge</span>
                    </h2>
                    <p class="text-xs text-stone-500">Sesuaikan karakter AI dengan jenis hotel (Resort santai vs Hotel bisnis formal)</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#F3EAD5] text-[#571724] border border-[#DFCFA8]">
                    <span>Aktif:</span>
                    <strong>
                        @if($currentPersona === 'bali_resort') Bali Resort (Hangat & Santai)
                        @elseif($currentPersona === 'luxury_heritage') Luxury Heritage (Anggun Tradisional)
                        @else Jakarta Business (Formal & Efisien)
                        @endif
                    </strong>
                </span>
            </div>

            <form action="{{ route('admin.persona.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
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
                        Gaya bahasa formal, efisien, solutif, cepat, dan presisi tinggi khas hotel bintang lima kawasan bisnis metropolitan.
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

        <!-- Section: Formulir Booking Tamu (Follow Up WhatsApp) -->
        <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-[#EFE5D3] pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛎️</span>
                    <div>
                        <h2 class="font-serif text-lg font-bold text-[#3B121C]">Daftar Formulir Booking Tamu (Lead Reservasi)</h2>
                        <p class="text-xs text-stone-500">Tamu yang telah mengisi form booking di chat untuk difollow-up pembayarannya via WhatsApp</p>
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
                                <th class="pb-2">Kamar</th>
                                <th class="pb-2">Tanggal</th>
                                <th class="pb-2">Hubungi Tamu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F2E8D7] text-stone-800">
                            @foreach ($guestBookings as $bkg)
                                <tr>
                                    <td class="py-2.5 font-mono font-bold text-[#571724]">{{ $bkg->booking_code }}</td>
                                    <td class="py-2.5 font-bold">{{ $bkg->guest_name }}</td>
                                    <td class="py-2.5 font-mono">{{ $bkg->guest_phone }}</td>
                                    <td class="py-2.5">{{ $bkg->room_type }}</td>
                                    <td class="py-2.5 text-stone-500">{{ $bkg->check_in_date->format('d/m/Y') }} – {{ $bkg->check_out_date->format('d/m/Y') }}</td>
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

    </main>

</body>
</html>
