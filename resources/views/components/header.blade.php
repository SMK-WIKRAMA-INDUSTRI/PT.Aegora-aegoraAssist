<header class="w-full bg-[#FAF5E8] border-b border-[#EBE1CF] px-4 sm:px-8 py-3 sticky top-0 z-40 backdrop-blur-md bg-[#FAF5E8]/90">
    <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
        <!-- Brand & Status -->
        <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
            <div class="w-11 h-11 rounded-xl overflow-hidden border border-[#571724]/20 shadow-xs shrink-0 bg-[#350711] flex items-center justify-center">
                <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Assist Logo" class="w-full h-full object-cover">
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-serif font-bold text-lg text-[#3A111C] tracking-tight group-hover:text-[#571724] transition">Aegora Assist</h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        ONLINE
                    </span>
                </div>
                <p class="text-[11px] text-stone-500 tracking-wide hidden sm:block">Your Hotel Assistant, Available 24/7</p>
            </div>
        </a>

        <!-- Navigation & Actions -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Beranda Link -->
            <a href="{{ route('landing') }}" class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-stone-700 text-xs font-semibold hover:bg-[#EFE7D4] transition border border-transparent hover:border-[#DFCFA8]">
                <span>🏠 Beranda</span>
            </a>

            <!-- Hubungi Staf -->
            <button type="button" id="nav-contact-staff-btn" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[#4A1521] text-xs font-semibold hover:bg-[#F0E6D2] border border-[#DFCFA8] transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="hidden sm:inline" data-i18n="nav_staff">Hubungi Staf</span>
            </button>

            <!-- Language Switcher (Bilingual ID / EN) -->
            <div class="flex items-center text-[11px] font-semibold bg-[#EFE7D4] p-0.5 rounded-xl border border-[#DFCFA8]">
                <button type="button" id="lang-id-btn" title="Bahasa Indonesia" class="px-2.5 py-1 rounded-lg transition-all text-[#571724] font-bold bg-[#FAF5E8] shadow-xs">ID</button>
                <button type="button" id="lang-en-btn" title="English" class="px-2.5 py-1 rounded-lg transition-all text-stone-500 hover:text-stone-800">EN</button>
            </div>

            <!-- Portal Staf / Admin -->
            <a href="{{ route('login') }}" title="Portal Staf Hotel" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-[#571724] bg-[#F7EFE2] border border-[#DECFA8] hover:bg-[#EFE3CF] transition">
                <span>Staf</span>
            </a>

            <!-- New Chat / Clear -->
            <button type="button" id="new-chat-btn" title="Mulai Percakapan Baru" class="p-2 rounded-xl text-stone-600 hover:bg-[#EFE7D4] border border-[#DFCFA8] transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>
    </div>
</header>
