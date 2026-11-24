<div class="w-full bg-[#FAF5E8]/80 border border-[#E9DFCB] rounded-2xl px-4 py-2.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 shadow-xs">
    <div class="flex items-center gap-2.5">
        <span class="w-2.5 h-2.5 rounded-full bg-[#571724]"></span>
        <div>
            <h2 class="text-xs sm:text-sm font-bold text-[#3B121C] tracking-wide flex items-center gap-1.5" data-i18n="sub_title">
                Aegora Assist Concierge
            </h2>
            <p class="text-[11px] text-stone-500" data-i18n="sub_desc">
                Online • Respon instan dalam hitungan detik <span class="hidden sm:inline">| PT Aegora Hospitality</span>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        @auth
            <a href="{{ route('profile') }}" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-[#F5EAD4] text-[#571724] border border-[#DFCFA8] hover:bg-[#EFE3CF] transition shadow-2xs">
                <span>📜 Riwayat Chat</span>
            </a>
        @endauth
        <div class="flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-[#F8E3D8] text-[#571724] border border-[#ECD1C3]">
            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span data-i18n="verified_guest">Terverifikasi Tamu</span>
        </div>
    </div>
</div>
