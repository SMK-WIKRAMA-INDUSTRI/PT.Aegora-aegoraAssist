<header class="w-full bg-[#FAF5E8] border-b border-[#EBE1CF] px-4 sm:px-8 py-3 sticky top-0 z-40 backdrop-blur-md bg-[#FAF5E8]/90">
    <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
        <!-- Brand & Status -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#571724] text-amber-200 flex items-center justify-center shadow-xs shrink-0">
                <!-- Hotel Emblem SVG -->
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2L3 7v13a2 2 0 002 2h14a2 2 0 002-2V7l-9-5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 22V12m0 0L8 8m4 4l4-4" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-serif font-bold text-lg text-[#3A111C] tracking-tight">Aegora Assist</h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        ONLINE
                    </span>
                </div>
                <p class="text-[11px] text-stone-500 tracking-wide hidden sm:block">Your Hotel Assistant, Available 24/7</p>
            </div>
        </div>

        <!-- Navigation & Actions -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Percakapan (Active) -->
            <button type="button" id="nav-conversations-btn" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#571724] text-white text-xs font-medium hover:bg-[#46111C] transition shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Percakapan</span>
            </button>

            <!-- Hubungi Staf -->
            <button type="button" id="nav-contact-staff-btn" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-[#4A1521] text-xs font-medium hover:bg-[#F0E6D2] border border-[#DFCFA8] transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="hidden sm:inline">Hubungi Staf</span>
            </button>

            <!-- Language Switcher -->
            <div class="hidden sm:flex items-center text-[11px] font-semibold text-stone-600 bg-[#EFE7D4] px-2.5 py-1.5 rounded-xl border border-[#DFCFA8]">
                <button type="button" class="text-[#571724] font-bold">ID</button>
                <span class="mx-1 text-stone-400">|</span>
                <button type="button" class="text-stone-500 hover:text-stone-800">EN</button>
            </div>

            <!-- New Chat / Clear -->
            <button type="button" id="new-chat-btn" title="Mulai Percakapan Baru" class="p-2 rounded-xl text-stone-600 hover:bg-[#EFE7D4] border border-[#DFCFA8] transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>

            <!-- User Avatar -->
            <div class="w-8 h-8 rounded-full bg-[#571724] text-amber-200 flex items-center justify-center font-serif font-bold text-xs shadow-xs shrink-0">
                G
            </div>
        </div>
    </div>
</header>
