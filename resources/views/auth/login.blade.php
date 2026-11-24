<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Aegora Assist</title>
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
            <h2 class="font-serif text-2xl font-bold text-[#3B121C]">Selamat Datang</h2>
            <p class="text-xs text-stone-500">Masuk untuk mengakses AI Concierge 24/7 dan riwayat percakapan</p>
        </div>

        <!-- Role Selector Tab -->
        <div class="grid grid-cols-2 p-1 bg-[#F0E7D3] rounded-2xl border border-[#DFCFA8] text-xs font-bold text-center">
            <button type="button" id="tab-user-btn" onclick="switchLoginMode('user')" class="py-2 rounded-xl transition shadow-xs bg-[#571724] text-white">
                👤 Tamu / Tamu Hotel
            </button>
            <button type="button" id="tab-staff-btn" onclick="switchLoginMode('staff')" class="py-2 rounded-xl transition text-stone-600 hover:text-stone-900">
                👔 Portal Staf
            </button>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl p-3">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl p-3 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Form Login -->
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" id="email-label" class="block text-xs font-bold text-[#3B121C] mb-1">Alamat Email Tamu</label>
                <input type="email" id="email" name="email" value="{{ old('email', $activeRole === 'staff' ? 'admin@aegora.com' : 'tamu@aegora.com') }}" required class="w-full px-4 py-2.5 rounded-xl bg-[#F7F2E4] border border-[#DFCFA8] text-xs sm:text-sm text-stone-800 focus:outline-none focus:border-[#571724] focus:ring-1 focus:ring-[#571724]">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-[#3B121C] mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" value="password123" required class="w-full px-4 py-2.5 pr-11 rounded-xl bg-[#F7F2E4] border border-[#DFCFA8] text-xs sm:text-sm text-stone-800 focus:outline-none focus:border-[#571724] focus:ring-1 focus:ring-[#571724]">
                    <button type="button" onclick="togglePasswordVisibility('password', this)" aria-label="Tampilkan atau sembunyikan kata sandi" title="Tampilkan / Sembunyikan Password" class="absolute inset-y-0 right-0 px-3 flex items-center text-stone-500 hover:text-[#571724] transition">
                        <svg class="w-4 h-4 eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg class="w-4 h-4 eye-closed hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-[#DFCFA8] text-[#571724] focus:ring-0" checked>
                    <span class="text-stone-600">Ingat sesi saya</span>
                </label>
                <a href="{{ route('landing') }}" class="text-[#571724] font-semibold hover:underline">Ke Beranda</a>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-3 rounded-xl bg-[#571724] text-white font-bold text-xs sm:text-sm hover:bg-[#440F1B] active:scale-[0.98] transition shadow-md">
                Masuk ke Concierge Chat
            </button>
        </form>

        <!-- Register Link for Guests -->
        <div id="register-prompt" class="text-center text-xs text-stone-600 pt-2 border-t border-[#EBE1CF]">
            Belum memiliki akun tamu? 
            <a href="{{ route('register') }}" class="font-bold text-[#571724] hover:underline">Daftar Sekarang</a>
        </div>

        <!-- Quick Demo Hint -->
        <div class="bg-[#F3EAD5] border border-[#DECFA8] rounded-2xl p-3 text-center text-xs space-y-1">
            <span class="font-bold text-[#571724] text-[11px] uppercase tracking-wide">💡 Akun Demo Siap Uji:</span>
            <p id="demo-hint-user" class="text-[11px] text-stone-600">Tamu: <code>tamu@aegora.com</code> | Pass: <code>password123</code></p>
            <p id="demo-hint-staff" class="text-[11px] text-stone-600 hidden">Staf: <code>admin@aegora.com</code> | Pass: <code>password123</code></p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            const openIcon = btn.querySelector('.eye-open');
            const closedIcon = btn.querySelector('.eye-closed');
            if (openIcon && closedIcon) {
                openIcon.classList.toggle('hidden', isHidden);
                closedIcon.classList.toggle('hidden', !isHidden);
            }
        }

        function switchLoginMode(mode) {
            const userBtn = document.getElementById('tab-user-btn');
            const staffBtn = document.getElementById('tab-staff-btn');
            const emailInput = document.getElementById('email');
            const emailLabel = document.getElementById('email-label');
            const submitBtn = document.getElementById('submit-btn');
            const registerPrompt = document.getElementById('register-prompt');
            const hintUser = document.getElementById('demo-hint-user');
            const hintStaff = document.getElementById('demo-hint-staff');

            if (mode === 'staff') {
                staffBtn.className = 'py-2 rounded-xl transition shadow-xs bg-[#571724] text-white';
                userBtn.className = 'py-2 rounded-xl transition text-stone-600 hover:text-stone-900';
                emailLabel.textContent = 'Alamat Email Staf Hotel';
                emailInput.value = 'admin@aegora.com';
                submitBtn.textContent = 'Masuk ke Dashboard Operasional';
                registerPrompt.classList.add('hidden');
                hintUser.classList.add('hidden');
                hintStaff.classList.remove('hidden');
            } else {
                userBtn.className = 'py-2 rounded-xl transition shadow-xs bg-[#571724] text-white';
                staffBtn.className = 'py-2 rounded-xl transition text-stone-600 hover:text-stone-900';
                emailLabel.textContent = 'Alamat Email Tamu';
                emailInput.value = 'tamu@aegora.com';
                submitBtn.textContent = 'Masuk ke Concierge Chat';
                registerPrompt.classList.remove('hidden');
                hintUser.classList.remove('hidden');
                hintStaff.classList.add('hidden');
            }
        }

        // Initialize based on URL or passed role
        @if ($activeRole === 'staff')
            switchLoginMode('staff');
        @endif
    </script>

</body>
</html>
