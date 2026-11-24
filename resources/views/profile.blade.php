<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & Riwayat Percakapan — Aegora Assist</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#FAF5E8] text-[#33221C] font-['Plus_Jakarta_Sans',sans-serif] min-h-screen flex flex-col antialiased selection:bg-[#571724] selection:text-white">

    <!-- Header Navigation -->
    <header class="w-full bg-[#FAF5E8]/90 backdrop-blur-md border-b border-[#EBE1CF] sticky top-0 z-40 px-4 sm:px-8 py-3.5">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl overflow-hidden border border-[#571724]/20 shadow-xs shrink-0 bg-[#350711] flex items-center justify-center">
                    <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Assist Logo" class="w-full h-full object-cover">
                </div>
                <div>
                    <h1 class="font-serif font-bold text-base sm:text-lg text-[#3A111C] tracking-tight group-hover:text-[#571724] transition">Aegora Assist</h1>
                    <p class="text-[10px] text-stone-500 uppercase tracking-widest font-semibold">Profil Tamu & Riwayat</p>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ route('chat') }}" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-[#571724] text-white hover:bg-[#430F1A] shadow-xs transition active:scale-95">
                    <span>💬 Buka Chat Concierge</span>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-800 border border-rose-200 hover:bg-rose-50 transition">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-8 py-8 space-y-8">
        
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm rounded-2xl p-4 flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Profile Header Card -->
        <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 sm:p-8 shadow-sm grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
            <div class="md:col-span-8 flex items-center gap-5">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-[#571724] text-amber-200 font-serif font-bold text-2xl sm:text-3xl flex items-center justify-center shadow-md shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#3B121C]">{{ $user->name }}</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-[#571724] border border-[#DFCFA8]">
                            {{ $user->role === 'staff' ? 'Staf Hotel' : 'Tamu Terverifikasi' }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-stone-600">{{ $user->email }} • Telp: {{ $user->phone ?? 'Belum diisi' }}</p>
                    <p class="text-[11px] text-stone-400">Bergabung sejak {{ $user->created_at ? $user->created_at->format('d M Y') : 'Hari ini' }}</p>
                </div>
            </div>
            <div class="md:col-span-4 flex md:justify-end gap-3">
                <button type="button" onclick="document.getElementById('edit-profile-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#FAF5E8] border border-[#DFCFA8] text-[#4A1521] hover:bg-[#F3EAD7] transition shadow-xs">
                    ✏️ Edit Profil
                </button>
                <a href="{{ route('chat') }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#571724] text-white hover:bg-[#430F1A] transition shadow-xs">
                    + Sesi Chat Baru
                </a>
            </div>
        </div>

        <!-- Chat History Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-lg sm:text-xl font-bold text-[#3B121C]">Riwayat Percakapan Concierge</h3>
                    <p class="text-xs text-stone-500">Daftar sesi tanya-jawab Anda dengan Aegora Assist</p>
                </div>
                <span class="text-xs font-bold text-stone-600 bg-[#F3EAD5] px-3 py-1 rounded-xl border border-[#DFCFA8]">
                    Total {{ $conversations->total() }} Sesi
                </span>
            </div>

            @if ($conversations->isEmpty())
                <div class="bg-[#FBF8F1] border border-dashed border-[#DFCFA8] rounded-3xl p-12 text-center space-y-4">
                    <div class="text-4xl">💬</div>
                    <div class="space-y-1">
                        <h4 class="font-serif font-bold text-base text-[#3B121C]">Belum Ada Riwayat Percakapan</h4>
                        <p class="text-xs text-stone-500 max-w-sm mx-auto">Anda belum memulai obrolan dengan Aegora Assist. Mulai obrolan untuk memesan fasilitas, makanan, atau reservasi kamar.</p>
                    </div>
                    <a href="{{ route('chat') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#571724] text-white text-xs font-bold hover:bg-[#440F1B] transition shadow-sm">
                        Mulai Obrolan Sekarang →
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($conversations as $conv)
                        <div class="bg-[#FBF8F1] border border-[#E5DAC4] hover:border-[#571724]/40 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-[11px] text-stone-500">
                                    <span class="inline-flex items-center gap-1 font-semibold text-[#571724]">
                                        <span>💬</span>
                                        <span>{{ $conv->messages_count }} Pesan</span>
                                    </span>
                                    <span>{{ $conv->updated_at->diffForHumans() }}</span>
                                </div>
                                <h4 class="font-bold text-sm text-[#3B121C] group-hover:text-[#571724] transition line-clamp-2">
                                    {{ $conv->title ?: 'Percakapan Tanpa Judul' }}
                                </h4>
                                @if ($conv->messages->first())
                                    <p class="text-xs text-stone-500 line-clamp-2 italic">
                                        "{{ Str::limit(strip_tags($conv->messages->first()->content), 80) }}"
                                    </p>
                                @endif
                            </div>

                            <div class="pt-2 border-t border-[#EFE5D3] flex items-center justify-between">
                                <span class="text-[10px] text-stone-400 font-mono">
                                    {{ $conv->created_at->format('d M Y, H:i') }}
                                </span>
                                <a href="{{ route('chat') }}?session_id={{ $conv->session_id }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#571724] hover:underline">
                                    <span>Lanjutkan Chat</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>
    </main>

    <!-- Modal Edit Profil -->
    <div id="edit-profile-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-[#E9DFCE] pb-3">
                <h3 class="font-serif text-lg font-bold text-[#3B121C]">Perbarui Profil Tamu</h3>
                <button type="button" onclick="document.getElementById('edit-profile-modal').classList.add('hidden')" class="text-stone-400 hover:text-stone-700 p-1 rounded-lg">
                    ✕
                </button>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" class="space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-stone-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#F5EFE2] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                </div>

                <div>
                    <label class="block font-bold text-stone-700 mb-1">Nomor WhatsApp / HP</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-[#F5EFE2] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                </div>

                <div>
                    <label class="block font-bold text-stone-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#F5EFE2] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                </div>

                <div class="pt-2 border-t border-[#EFE5D3]">
                    <label class="block font-bold text-stone-700 mb-1">Kata Sandi Baru (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin diubah" class="w-full px-3.5 py-2.5 rounded-xl bg-[#F5EFE2] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                </div>

                <div>
                    <label class="block font-bold text-stone-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" name="password_confirmation" placeholder="Ulangi kata sandi baru" class="w-full px-3.5 py-2.5 rounded-xl bg-[#F5EFE2] border border-[#DFCFA8] text-stone-800 focus:outline-none focus:border-[#571724]">
                </div>

                <div class="pt-2 flex justify-end gap-2.5">
                    <button type="button" onclick="document.getElementById('edit-profile-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-[#DFCFA8] text-stone-600 font-semibold hover:bg-[#F3EAD7]">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#571724] text-white font-bold hover:bg-[#440F1B] shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
