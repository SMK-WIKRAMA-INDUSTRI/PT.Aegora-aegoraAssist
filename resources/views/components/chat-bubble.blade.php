@props([
    'role' => 'user',
    'content' => '',
    'time' => now()->format('H:i') . ' WIB',
    'facility' => null
])

@if ($role === 'user')
    <div class="flex justify-end w-full group animate-in fade-in slide-in-from-bottom-2 duration-200">
        <div class="max-w-xl flex flex-col items-end">
            <div class="bg-[#F8F2E4] border border-[#E7DDC5] rounded-2xl rounded-tr-xs px-5 py-3.5 shadow-2xs text-stone-800 text-xs sm:text-sm leading-relaxed">
                <p class="whitespace-pre-wrap">{!! nl2br(e($content)) !!}</p>
            </div>
            <div class="flex items-center gap-1.5 mt-1 mr-1 text-[11px] text-stone-400">
                <span>{{ $time }}</span>
                <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                    <path d="M12.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-1-1a1 1 0 011.414-1.414L7 11.586l4.293-4.293a1 1 0 011.414 0z"/>
                </svg>
            </div>
        </div>
    </div>
@else
    <div class="flex items-start gap-3 w-full group animate-in fade-in slide-in-from-bottom-2 duration-200">
        <!-- AI Avatar -->
        <div class="w-8 h-8 rounded-xl overflow-hidden border border-[#571724]/20 shadow-xs shrink-0 mt-0.5 bg-[#350711]">
            <img src="{{ asset('images/aegora-logo.jpg') }}" alt="Aegora Assist" class="w-full h-full object-cover">
        </div>

        <div class="flex-1 max-w-3xl space-y-3">
            <div class="bg-[#FDFBF7] border border-[#E8DFCB] rounded-2xl rounded-tl-xs p-5 sm:p-6 shadow-xs space-y-4">
                <!-- Meta Header -->
                <div class="flex items-center justify-between border-b border-[#F0E8D7] pb-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-[#3B121C]">Aegora Assist</span>
                        <span class="text-[10px] text-stone-400 font-medium">• AI Concierge Resmi</span>
                    </div>
                    <span class="text-[11px] text-stone-400">{{ $time }}</span>
                </div>

                <!-- Message Body -->
                <div class="prose prose-stone text-xs sm:text-sm text-stone-700 leading-relaxed max-w-none">
                    {!! Str::markdown($content) !!}
                </div>

                <!-- Facility Card (if present) -->
                @if ($facility)
                    <div class="mt-4 rounded-2xl overflow-hidden border border-[#E4D9C1] bg-[#FAF5E8] shadow-xs">
                        <!-- Image Container with Overlays -->
                        <div class="relative h-48 sm:h-56 w-full overflow-hidden bg-stone-900">
                            <img src="{{ $facility['image'] }}" alt="{{ $facility['title'] }}" class="w-full h-full object-cover opacity-90 hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>
                            
                            <!-- Badges on Image -->
                            <div class="absolute bottom-3 left-4 right-4 flex flex-col sm:flex-row sm:items-end justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-semibold text-amber-300 tracking-wider uppercase bg-black/40 px-2 py-0.5 rounded-sm">
                                        {{ $facility['location'] }}
                                    </span>
                                    <h3 class="font-serif text-lg sm:text-xl font-bold text-white drop-shadow-sm mt-0.5">
                                        {{ $facility['title'] }}
                                    </h3>
                                </div>
                                <span class="self-start sm:self-end text-[11px] font-medium text-stone-200 bg-white/20 backdrop-blur-xs px-2.5 py-1 rounded-full border border-white/20">
                                    🕒 {{ $facility['hours'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Facility Tags / Perks -->
                        @if (!empty($facility['tags']))
                            <div class="p-3.5 flex flex-wrap items-center gap-2 border-b border-[#EBE1CD] bg-[#F7F1E1]">
                                @foreach ($facility['tags'] as $tag)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-[#4A1521] bg-[#EFE5D3] border border-[#DFCFA8] px-2.5 py-1 rounded-full">
                                        <span class="text-amber-700">✦</span>
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        @if (!empty($facility['actions']))
                            <div class="p-3.5 flex flex-wrap gap-2">
                                @foreach ($facility['actions'] as $act)
                                    @if (!empty($act['primary']))
                                        <button type="button" 
                                                data-action="{{ $act['action'] ?? '' }}" 
                                                class="facility-action-btn flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#571724] hover:bg-[#46111C] text-white text-xs font-semibold shadow-xs transition active:scale-95">
                                            <span>📖</span>
                                            <span>{{ $act['label'] }}</span>
                                        </button>
                                    @else
                                        <button type="button" 
                                                data-action="{{ $act['action'] ?? '' }}" 
                                                class="facility-action-btn flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#F0E6D2] hover:bg-[#E7DBC2] text-[#4A1521] border border-[#DFCFA8] text-xs font-semibold transition active:scale-95">
                                            <span>{{ $loop->iteration == 2 ? '🍽️' : '🛎️' }}</span>
                                            <span>{{ $act['label'] }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Feedback Row -->
                <div class="pt-2 flex items-center justify-between border-t border-[#F2EADB] text-[11px] text-stone-400">
                    <div class="flex items-center gap-2">
                        <button type="button" class="speak-msg-btn inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-[#F5EFE2] hover:bg-[#EAE0CB] text-[#571724] font-semibold transition" data-speak-lang="id" title="Dengarkan dengan Suara AI Deepgram">
                            <span>🔊</span>
                            <span>Dengarkan</span>
                        </button>
                        <span class="hidden sm:inline">Apakah informasi ini membantu Anda?</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="feedback-btn hover:text-stone-700 p-1 rounded-md hover:bg-[#EFE8D6] transition" title="Membantu">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                            </svg>
                        </button>
                        <button type="button" class="feedback-btn hover:text-stone-700 p-1 rounded-md hover:bg-[#EFE8D6] transition" title="Kurang Membantu">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76 1.04m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
