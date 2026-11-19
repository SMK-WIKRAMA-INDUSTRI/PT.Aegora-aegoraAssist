<div class="space-y-1.5">
    <form id="chat-form" class="relative flex items-center bg-[#F7F2E4] border border-[#DFCFA8] focus-within:border-[#571724] focus-within:ring-2 focus-within:ring-[#571724]/20 rounded-2xl px-3 py-2 shadow-sm transition-all duration-150">
        <!-- Attachment Button -->
        <button type="button" id="attach-btn" title="Unggah Foto atau Dokumen" class="p-2 text-stone-500 hover:text-[#571724] hover:bg-[#EFE5D2] rounded-xl transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
        </button>

        <!-- Voice / Mic Button -->
        <button type="button" id="voice-btn" title="Pesan Suara" class="p-2 text-stone-500 hover:text-[#571724] hover:bg-[#EFE5D2] rounded-xl transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
        </button>

        <!-- Chat Input Field -->
        <input type="text" 
               id="chat-input" 
               name="message" 
               autocomplete="off" 
               placeholder="Tanyakan apa saja seputar reservasi, fasilitas, atau check-in..." 
               class="flex-1 bg-transparent px-3 py-1.5 text-xs sm:text-sm text-stone-800 placeholder-stone-400 focus:outline-hidden" />

        <!-- Send Button -->
        <button type="submit" 
                id="send-btn" 
                title="Kirim Pertanyaan" 
                class="w-9 h-9 rounded-xl bg-[#571724] hover:bg-[#46111C] active:scale-95 text-white flex items-center justify-center transition shadow-xs disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-4 h-4 transform rotate-45 -translate-x-0.5 -translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
        </button>
    </form>

    <!-- Security & Privacy Footer Info -->
    <div class="flex items-center justify-center gap-1.5 text-[10px] sm:text-[11px] text-stone-500 text-center px-2">
        <svg class="w-3 h-3 text-stone-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
        </svg>
        <span>
            Aegora Assist didukung data resmi PT Aegora • Enkripsi percakapan privat tamu hotel • Hubungi Front Desk ext. 0
        </span>
    </div>
</div>
