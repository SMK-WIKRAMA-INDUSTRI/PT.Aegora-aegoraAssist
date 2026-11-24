<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun Tamu — Aegora Assist</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#FAF5E8] text-[#33221C] font-['Plus_Jakarta_Sans',sans-serif] min-h-screen flex items-center justify-center p-4 selection:bg-[#571724] selection:text-white">

    <div class="w-full max-w-md bg-[#FBF8F1] border border-[#E5DAC4] rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <!-- Logo & Header -->
        <div class="text-center space-y-2">
            <div class="w-14 h-14 rounded-2xl overflow-hidden mx-auto bg-[#350711] border border-[#571724]/20 shadow-md">
                <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Logo" class="w-full h-full object-cover">
            </div>
            <h2 class="font-serif text-2xl font-bold text-[#3B121C]">Daftar Akun Tamu</h2>
            <p class="text-xs text-stone-500">Nikmati kemudahan layanan concierge, pemesanan kamar, & riwayat percakapan</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl p-3 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Form Register -->
        <form action="{{ route('register') }}" method="POST" class="space-y-3.5 text-xs">
            @csrf
            <div>
                <label for="name" class="block font-bold text-[#3B121C] mb-1">Nama Lengkap Tamu</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Hendra Wijaya" required class="w-full px-4 py-2.5 rounded-xl bg-[#F7F2E4] border border-[#DFCFA8] text-xs sm:text-sm text-stone-800 focus:outline-none focus:border-[#571724]">
            </div>

            <div>
                <label for="phone" class="block font-bold text-[#3B121C] mb-1">Nomor WhatsApp / HP</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 081234567890" required class="w-full px-4 py-2.5 rounded-xl bg-[#F7F2E4] border border-[#DFCFA8] text-xs sm:text-sm text-stone-800 focus:outline-none focus:border-[#571724]">
            </div>

            <div>
                <label for="email" class="block font-bold text-[#3B121C] mb-1">Alamat Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required class="w-full px-4 py-2.5 rounded-xl bg-[#F7F2E4] border border-[#DFCFA8] text-xs sm:text-sm text-stone-800 focus:outline-none focus:border-[#571724]">
            </div>

            <div>
                <label for="password" class="block font-bold text-[#3B121C] mb-1">Kata Sandi (Min. 6 Karakter)</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-2.5 rounded-xl bg-[#F7F2E4] border border-[#DFCFA8] text-xs sm:text-sm text-stone-800 focus:outline-none focus:border-[#571724]">
            </div>

            <div>
                <label for="password_confirmation" class="block font-bold text-[#3B121C] mb-1">Konfirmasi Kata Sandi</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required class="w-full px-4 py-2.5 rounded-xl bg-[#F7F2E4] border border-[#DFCFA8] text-xs sm:text-sm text-stone-800 focus:outline-none focus:border-[#571724]">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 rounded-xl bg-[#571724] text-white font-bold text-xs sm:text-sm hover:bg-[#440F1B] active:scale-[0.98] transition shadow-md">
                    Daftar & Mulai Chat Concierge →
                </button>
            </div>
        </form>

        <div class="text-center text-xs text-stone-600 pt-2 border-t border-[#EBE1CF]">
            Sudah memiliki akun? 
            <a href="{{ route('login') }}" class="font-bold text-[#571724] hover:underline">Masuk di Sini</a>
        </div>
    </div>

</body>
</html>
