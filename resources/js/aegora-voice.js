const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

// Cached Deepgram Voice configuration loaded from backend (/api/voice/config)
let deepgramConfig = {
    configured: false,
    active_voices: { id: 'flux-fitri-id', en: 'aura-2-helena-en' },
    voices: { id: {}, en: {} },
};

let activeAudioElement = null;
let activeAudioUrl = null;
let selectedVoiceOverride = localStorage.getItem('aegora_voice_override') || '';

function getActiveLocale() {
    const lang = localStorage.getItem('aegora_lang') || 'id';
    return lang === 'en' ? 'en-US' : 'id-ID';
}

function getShortLang(code) {
    if (!code) return (localStorage.getItem('aegora_lang') || 'id').slice(0, 2).toLowerCase();
    return String(code).slice(0, 2).toLowerCase();
}

/**
 * Lightweight client-side language detector to mirror the guest's spoken language
 * automatically without requiring manual menu switches.
 */
function detectTextLanguage(text, fallback = 'id') {
    if (!text) return fallback;
    if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) return 'ja';
    if (/[\uAC00-\uD7AF]/.test(text)) return 'ko';
    if (/[\u4E00-\u9FFF]/.test(text)) return 'zh';
    if (/[\u0600-\u06FF]/.test(text)) return 'ar';

    const lower = ` ${text.toLowerCase()} `;
    const idHits = [' saya ', ' kami ', ' anda ', ' bapak', ' ibu ', ' kamar ', ' harga ', ' berapa ', ' bisa ', ' boleh ', ' tidak ', ' mau ', ' pesan ', ' sarapan ', ' kolam ', ' terima kasih', ' halo ', ' selamat ', ' tagihan ', ' reservasi ', ' jam ', ' lantai ', ' handuk ', ' tolong ', ' ya ', ' yang ', ' untuk ', ' dengan ', ' di ', ' ke '].filter(w => lower.includes(w)).length;
    const enHits = [' the ', ' is ', ' are ', ' you ', ' your ', ' would ', ' could ', ' room ', ' breakfast ', ' pool ', ' check-in ', ' check-out ', ' price ', ' rate ', ' available ', ' book ', ' reservation ', ' hello ', ' welcome ', ' thank you', ' please ', ' bill ', ' floor ', ' help ', ' what ', ' when ', ' how '].filter(w => lower.includes(w)).length;

    if (enHits > idHits && enHits >= 1) return 'en';
    if (idHits > enHits && idHits >= 1) return 'id';
    return fallback;
}

function stripMarkdown(text) {
    if (!text) return '';
    return text
        .replace(/```[\s\S]*?```/g, '')
        .replace(/`([^`]+)`/g, '$1')
        .replace(/\*\*([^*]+)\*\*/g, '$1')
        .replace(/\*([^*]+)\*/g, '$1')
        .replace(/__([^_]+)__/g, '$1')
        .replace(/_([^_]+)_/g, '$1')
        .replace(/#{1,6}\s+/g, '')
        .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
        .replace(/^[•\-\*]\s+/gm, '')
        .replace(/<[^>]*>/g, '')
        .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function stopCurrentPlayback() {
    if (activeAudioElement) {
        try {
            activeAudioElement.pause();
            activeAudioElement.currentTime = 0;
        } catch {}
        activeAudioElement = null;
    }
    if (activeAudioUrl) {
        try {
            URL.revokeObjectURL(activeAudioUrl);
        } catch {}
        activeAudioUrl = null;
    }
    if ('speechSynthesis' in window) {
        try {
            window.speechSynthesis.cancel();
        } catch {}
    }
}

/**
 * STRICT NON-GOOGLE NATURAL VOICE FALLBACK (only used if DEEPGRAM_API_KEY is not yet filled in .env).
 * Explicitly excludes all Google voices (e.g., "Google Bahasa Indonesia", "Google US English")
 * and prioritizes warm Microsoft Neural / Apple / native hospitality voices.
 */
function pickNonGoogleNaturalVoice(langCode) {
    if (!('speechSynthesis' in window)) return null;
    const voices = window.speechSynthesis.getVoices() || [];
    const prefix = getShortLang(langCode);

    // Filter OUT every Google voice
    const nonGoogle = voices.filter(v => {
        const name = (v.name || '').toLowerCase();
        const uri = (v.voiceURI || '').toLowerCase();
        return !name.includes('google') && !uri.includes('google');
    });

    const langMatched = nonGoogle.filter(v => (v.lang || '').toLowerCase().startsWith(prefix));

    // Rank warm neural / natural polite voices highest
    const preferredPatterns = prefix === 'id'
        ? ['gadis', 'natural', 'online', 'damayanti', 'ardi', 'microsoft', 'neural']
        : ['aria', 'jenny', 'ava', 'sonia', 'samantha', 'natural', 'online', 'microsoft', 'neural'];

    for (const pattern of preferredPatterns) {
        const found = langMatched.find(v => (v.name || '').toLowerCase().includes(pattern));
        if (found) return found;
    }

    if (langMatched.length > 0) return langMatched[0];

    // If no non-Google voice exists for that exact language, prefer Microsoft Multilingual / Natural non-Google voice
    const naturalMulti = nonGoogle.find(v => {
        const n = (v.name || '').toLowerCase();
        return n.includes('multilingual') || n.includes('natural');
    });

    return naturalMulti || nonGoogle[0] || null;
}

function speakWithNonGoogleFallback(text, langCode, onEndCallback) {
    if (!('speechSynthesis' in window)) {
        if (typeof onEndCallback === 'function') onEndCallback();
        return;
    }

    const clean = stripMarkdown(text);
    const shortLang = getShortLang(langCode || detectTextLanguage(clean));
    const bcp47Map = {
        id: 'id-ID',
        en: 'en-US',
        ja: 'ja-JP',
        fr: 'fr-FR',
        de: 'de-DE',
        es: 'es-ES',
        ko: 'ko-KR',
        zh: 'zh-CN',
        ar: 'ar-SA',
    };

    const utterance = new SpeechSynthesisUtterance(clean);
    utterance.lang = bcp47Map[shortLang] || 'id-ID';
    // Warm, poised, natural hotel receptionist pacing (neither rushed nor robotic)
    utterance.rate = 0.96;
    utterance.pitch = 1.02;

    const naturalVoice = pickNonGoogleNaturalVoice(shortLang);
    if (naturalVoice) {
        utterance.voice = naturalVoice;
    }

    utterance.onend = () => {
        if (typeof onEndCallback === 'function') onEndCallback();
    };
    utterance.onerror = () => {
        if (typeof onEndCallback === 'function') onEndCallback();
    };

    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
}

/**
 * Play MP3/audio binary returned from Deepgram TTS (/api/voice/speak or /api/voice/converse).
 */
function playAudioBlob(blob, onEndCallback) {
    stopCurrentPlayback();
    activeAudioUrl = URL.createObjectURL(blob);
    activeAudioElement = new Audio(activeAudioUrl);

    const finish = () => {
        if (activeAudioUrl) {
            try { URL.revokeObjectURL(activeAudioUrl); } catch {}
            activeAudioUrl = null;
        }
        activeAudioElement = null;
        if (typeof onEndCallback === 'function') onEndCallback();
    };

    activeAudioElement.onended = finish;
    activeAudioElement.onerror = finish;
    activeAudioElement.play().catch(finish);
}

function playBase64Audio(base64, contentType, onEndCallback) {
    const byteChars = atob(base64);
    const byteNums = new Array(byteChars.length);
    for (let i = 0; i < byteChars.length; i++) {
        byteNums[i] = byteChars.charCodeAt(i);
    }
    const blob = new Blob([new Uint8Array(byteNums)], { type: contentType || 'audio/mpeg' });
    playAudioBlob(blob, onEndCallback);
}

/**
 * Speak any text using Deepgram TTS (/api/voice/speak) first, automatically matching
 * the detected language & selected hotel CS voice character, with strict non-Google fallback.
 */
async function synthesizeAndPlay(text, langHint = null, onEndCallback = null, triggerBtn = null) {
    stopCurrentPlayback();
    const clean = stripMarkdown(text);
    if (!clean) {
        if (typeof onEndCallback === 'function') onEndCallback();
        return;
    }

    const detectedLang = getShortLang(langHint || detectTextLanguage(clean, localStorage.getItem('aegora_lang') || 'id'));
    const origBtnHtml = triggerBtn ? triggerBtn.innerHTML : null;
    if (triggerBtn) {
        triggerBtn.disabled = true;
        triggerBtn.innerHTML = '<span>🔊</span><span>Memutar...</span>';
    }

    const restoreBtn = () => {
        if (triggerBtn && origBtnHtml !== null) {
            triggerBtn.disabled = false;
            triggerBtn.innerHTML = origBtnHtml;
        }
        if (typeof onEndCallback === 'function') onEndCallback();
    };

    // Only apply explicit voice override if it matches the detected language
    let voiceParam = null;
    if (selectedVoiceOverride) {
        const overrideLang = selectedVoiceOverride.slice(-2);
        if (overrideLang === detectedLang) {
            voiceParam = selectedVoiceOverride;
        }
    }

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/api/voice/speak', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'audio/mpeg, application/json',
            },
            body: JSON.stringify({
                text: clean,
                lang: detectedLang,
                voice: voiceParam,
            }),
        });

        const contentType = res.headers.get('Content-Type') || '';
        if (res.ok && contentType.includes('audio')) {
            const audioBlob = await res.blob();
            playAudioBlob(audioBlob, restoreBtn);
            return;
        }

        // Backend returned JSON fallback (e.g. DEEPGRAM_API_KEY not entered yet)
        const json = await res.json().catch(() => ({}));
        const speakText = json?.data?.clean_text || clean;
        const resolvedLang = json?.data?.detected_language || detectedLang;
        speakWithNonGoogleFallback(speakText, resolvedLang, restoreBtn);
    } catch {
        speakWithNonGoogleFallback(clean, detectedLang, restoreBtn);
    }
}

window.aegoraSpeakText = (text, lang = null, btn = null) => {
    synthesizeAndPlay(text, lang, null, btn);
};

async function loadDeepgramConfig() {
    try {
        const res = await fetch('/api/voice/config', { headers: { Accept: 'application/json' } });
        if (res.ok) {
            const json = await res.json();
            if (json?.status === 'success' && json.data) {
                deepgramConfig = json.data;
            }
        }
    } catch {
        // Keep default fallback state
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDeepgramConfig();

    // Warm up non-Google SpeechSynthesis voices list in browsers
    if ('speechSynthesis' in window) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
    }

    const micBtn = document.getElementById('voice-btn');
    const chatInput = document.getElementById('chat-input');
    const voiceCallBtn = document.getElementById('voice-call-btn');

    let micRecognition = null;
    let micMediaRecorder = null;
    let micAudioChunks = [];
    let isMicRecording = false;

    // -------------------------------------------------------------------------
    // 1. Chat Input Dictation Mic Button (Deepgram Nova-3 STT or Web Speech)
    // -------------------------------------------------------------------------
    if (micBtn && chatInput) {
        const hasMediaRecorder = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
        if (!SpeechRecognition && !hasMediaRecorder) {
            micBtn.style.display = 'none';
        } else {
            micBtn.addEventListener('click', () => {
                if (isMicRecording) {
                    stopMicRecording();
                } else {
                    startMicRecording();
                }
            });
        }
    }

    async function startMicRecording() {
        // Prefer Deepgram Nova-3 STT when API key is configured (supports auto-detect language!)
        if (deepgramConfig.configured && navigator.mediaDevices?.getUserMedia && window.MediaRecorder) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                micAudioChunks = [];
                const mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : 'audio/webm';
                micMediaRecorder = new MediaRecorder(stream, { mimeType: mime });

                micMediaRecorder.ondataavailable = (e) => {
                    if (e.data && e.data.size > 0) micAudioChunks.push(e.data);
                };

                micMediaRecorder.onstop = async () => {
                    stream.getTracks().forEach(t => t.stop());
                    const blob = new Blob(micAudioChunks, { type: mime });
                    if (blob.size < 500) return;

                    const prevPlaceholder = chatInput.placeholder;
                    chatInput.placeholder = 'Deepgram sedang mengenali suara Anda...';
                    try {
                        const fd = new FormData();
                        fd.append('audio', blob, 'dictation.webm');
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/voice/transcribe', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                            body: fd,
                        });
                        const json = await res.json();
                        if (json?.status === 'success' && json.data?.transcript) {
                            chatInput.value = ((chatInput.value ? chatInput.value + ' ' : '') + json.data.transcript).trim();
                            chatInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    } catch {
                        // Silent fail
                    } finally {
                        chatInput.placeholder = prevPlaceholder;
                    }
                };

                isMicRecording = true;
                micBtn.classList.add('aegora-mic-recording');
                micMediaRecorder.start();
                return;
            } catch {
                // Fallback to browser SpeechRecognition below
            }
        }

        if (!SpeechRecognition) return;
        try {
            micRecognition = new SpeechRecognition();
            micRecognition.continuous = true;
            micRecognition.interimResults = true;
            micRecognition.lang = getActiveLocale();

            let baseText = chatInput.value ? chatInput.value + ' ' : '';

            micRecognition.onstart = () => {
                isMicRecording = true;
                micBtn.classList.add('aegora-mic-recording');
            };

            micRecognition.onresult = (event) => {
                let interim = '';
                let final = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        final += transcript;
                    } else {
                        interim += transcript;
                    }
                }
                chatInput.value = (baseText + final + interim).trim();
                chatInput.dispatchEvent(new Event('input', { bubbles: true }));
            };

            micRecognition.onerror = () => stopMicRecording();
            micRecognition.onend = () => stopMicRecording();
            micRecognition.start();
        } catch {
            stopMicRecording();
        }
    }

    function stopMicRecording() {
        isMicRecording = false;
        if (micBtn) micBtn.classList.remove('aegora-mic-recording');

        if (micMediaRecorder && micMediaRecorder.state !== 'inactive') {
            try { micMediaRecorder.stop(); } catch {}
            micMediaRecorder = null;
        }
        if (micRecognition) {
            try { micRecognition.stop(); } catch {}
            micRecognition = null;
        }
    }

    // -------------------------------------------------------------------------
    // 2. Full Interactive Voice Call Mode (Deepgram STS + Auto-Language + Warm CS)
    // -------------------------------------------------------------------------
    if (voiceCallBtn) {
        voiceCallBtn.addEventListener('click', async () => {
            await loadDeepgramConfig();
            initiateVoiceCall();
        });
    }

    function buildVoicePresetOptionsHtml() {
        const idVoices = deepgramConfig.voices?.id || {
            'flux-fitri-id': { name: 'Fitri — Ramah & Sopan (ID)' },
            'flux-dewi-id': { name: 'Dewi — Guest Relations (ID)' },
            'flux-teguh-id': { name: 'Teguh — Berwibawa (ID)' },
        };
        const enVoices = deepgramConfig.voices?.en || {
            'aura-2-helena-en': { name: 'Helena — Warm Concierge (EN)' },
            'aura-2-thalia-en': { name: 'Thalia — Poised CS (EN)' },
            'aura-2-apollo-en': { name: 'Apollo — Executive (EN)' },
        };

        let html = `<option value="">Otomatis Sesuai Bahasa Tamu (Default Hotel)</option>`;
        html += `<optgroup label="Bahasa Indonesia (Deepgram Flux)">`;
        Object.entries(idVoices).forEach(([id, meta]) => {
            html += `<option value="${id}" ${selectedVoiceOverride === id ? 'selected' : ''}>${meta.name}</option>`;
        });
        html += `</optgroup><optgroup label="English (Deepgram Aura-2)">`;
        Object.entries(enVoices).forEach(([id, meta]) => {
            html += `<option value="${id}" ${selectedVoiceOverride === id ? 'selected' : ''}>${meta.name}</option>`;
        });
        html += `</optgroup>`;
        return html;
    }

    function initiateVoiceCall() {
        const hasMediaRecorder = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
        if (!deepgramConfig.configured && !SpeechRecognition) {
            const isEn = (localStorage.getItem('aegora_lang') || 'id') === 'en';
            alert(isEn
                ? 'Speech recognition is not supported in this browser. Configure DEEPGRAM_API_KEY for full cloud Voice AI.'
                : 'Browser ini tidak mendukung pengenalan suara lokal. Isi DEEPGRAM_API_KEY di .env untuk mengaktifkan Deepgram Voice AI.');
            return;
        }
        if (deepgramConfig.configured && !hasMediaRecorder && !SpeechRecognition) {
            alert('Mikrofon tidak tersedia di browser ini.');
            return;
        }

        stopMicRecording();
        stopCurrentPlayback();

        let isCallActive = true;
        let isMuted = false;
        let callRecognition = null;
        let stsRecorder = null;
        let stsStream = null;
        let stsAudioContext = null;
        let stsRafId = null;
        let callTimerInterval = null;
        let secondsElapsed = 0;
        let activeSpokenLang = getShortLang(localStorage.getItem('aegora_lang') || 'id');

        const isEn = activeSpokenLang === 'en';
        const labels = {
            listening: isEn ? 'Listening to you...' : 'Mendengarkan Anda...',
            thinking: isEn ? 'Concierge is preparing a reply...' : 'Aegora Assist sedang menyiapkan jawaban...',
            speaking: isEn ? 'Aegora Concierge is speaking...' : 'Aegora Assist sedang berbicara...',
            endCall: isEn ? 'End Call' : 'Akhiri Panggilan',
            mute: isEn ? 'Mute' : 'Bisukan',
            unmute: isEn ? 'Unmute' : 'Aktifkan',
            sendVoice: isEn ? 'Send Speech' : 'Kirim Suara',
            micDenied: isEn ? 'Microphone access denied.' : 'Izin mikrofon ditolak.',
        };

        const providerBadgeText = deepgramConfig.configured
            ? 'Deepgram Voice AI'
            : 'Siap Deepgram API (Non-Google)';

        const overlay = document.createElement('div');
        overlay.className = 'aegora-voice-overlay';
        overlay.innerHTML = `
            <div class="w-full max-w-lg flex flex-col gap-3 border-b border-[#DFCFA8] pb-4">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl overflow-hidden bg-[#571724] shadow-xs">
                            <img src="/images/aegora-logo.jpg" alt="Aegora" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h3 class="font-serif font-bold text-sm text-[#3B121C]">Aegora Concierge Voice</h3>
                            <p class="text-[11px] text-stone-500">
                                <span id="aegora-call-timer">00:00</span> • <span id="aegora-call-lang-badge" class="font-semibold text-[#571724]">🌐 Auto: ${activeSpokenLang.toUpperCase()}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#EFE5D3] text-[#571724] border border-[#DFCFA8]">
                        <span class="w-2 h-2 rounded-full ${deepgramConfig.configured ? 'bg-emerald-500' : 'bg-amber-500'} animate-pulse"></span>
                        <span>${providerBadgeText}</span>
                    </div>
                </div>

                <!-- Karakter Suara & Bahasa Selector -->
                <div class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
                    <div class="flex items-center gap-1.5 flex-1 min-w-[200px]">
                        <span class="text-stone-500 font-semibold shrink-0">Suara CS:</span>
                        <select id="aegora-voice-preset-select" class="flex-1 px-2.5 py-1.5 rounded-xl bg-[#FBF8F1] border border-[#DFCFA8] text-[#3B121C] font-medium text-[11px] focus:outline-none focus:border-[#571724]">
                            ${buildVoicePresetOptionsHtml()}
                        </select>
                    </div>
                    ${!deepgramConfig.configured ? `
                        <div class="flex items-center gap-1 bg-[#EFE5D3] p-0.5 rounded-xl border border-[#DFCFA8]">
                            <button type="button" data-voice-lang="id" class="aegora-vlang-btn px-2 py-1 rounded-lg font-bold ${activeSpokenLang === 'id' ? 'bg-[#571724] text-white' : 'text-stone-600'}">ID</button>
                            <button type="button" data-voice-lang="en" class="aegora-vlang-btn px-2 py-1 rounded-lg font-bold ${activeSpokenLang === 'en' ? 'bg-[#571724] text-white' : 'text-stone-600'}">EN</button>
                        </div>
                    ` : ''}
                </div>
            </div>

            <div class="flex flex-col items-center justify-center space-y-6 my-auto text-center max-w-md w-full px-4">
                <div class="aegora-voice-orb listening" id="aegora-call-orb">
                    <div class="flex items-center justify-center h-8" id="aegora-call-waves">
                        <div class="aegora-voice-wave-bar"></div>
                        <div class="aegora-voice-wave-bar"></div>
                        <div class="aegora-voice-wave-bar"></div>
                        <div class="aegora-voice-wave-bar"></div>
                        <div class="aegora-voice-wave-bar"></div>
                    </div>
                </div>

                <div class="space-y-2 w-full">
                    <p class="text-sm font-bold text-[#571724] tracking-wide" id="aegora-call-status">${labels.listening}</p>
                    <p class="text-xs text-stone-600 italic min-h-[3.5rem] px-3 leading-relaxed" id="aegora-call-transcript">
                        ${deepgramConfig.configured
                            ? 'Silakan berbicara dalam Bahasa Indonesia, Inggris, atau bahasa pilihan Anda...'
                            : 'Silakan berbicara — suara Aegora disesuaikan ramah & sopan (isi DEEPGRAM_API_KEY di .env untuk Deepgram Cloud Voice).'}
                    </p>
                </div>
            </div>

            <div class="w-full max-w-md flex items-center justify-center gap-4 pt-4 border-t border-[#DFCFA8]">
                <button type="button" id="aegora-mute-btn" class="flex flex-col items-center gap-1.5 p-3 px-4 rounded-2xl bg-[#EFE5D3] hover:bg-[#E5D7BF] text-stone-700 transition active:scale-95 text-xs font-semibold">
                    <svg class="w-5 h-5 text-[#571724]" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="aegora-mute-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span id="aegora-mute-label">${labels.mute}</span>
                </button>

                ${deepgramConfig.configured ? `
                    <button type="button" id="aegora-send-turn-btn" class="hidden flex flex-col items-center gap-1.5 p-3 px-5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white transition active:scale-95 text-xs font-semibold shadow-md">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>${labels.sendVoice}</span>
                    </button>
                ` : ''}

                <button type="button" id="aegora-end-call-btn" class="flex flex-col items-center gap-1.5 p-3 px-6 rounded-2xl bg-[#571724] hover:bg-[#46111C] text-white transition active:scale-95 text-xs font-semibold shadow-md">
                    <svg class="w-5 h-5 transform rotate-[135deg]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
                    </svg>
                    <span>${labels.endCall}</span>
                </button>
            </div>
        `;

        document.body.appendChild(overlay);

        const statusEl = document.getElementById('aegora-call-status');
        const transcriptEl = document.getElementById('aegora-call-transcript');
        const orbEl = document.getElementById('aegora-call-orb');
        const muteBtn = document.getElementById('aegora-mute-btn');
        const muteLabel = document.getElementById('aegora-mute-label');
        const endCallBtn = document.getElementById('aegora-end-call-btn');
        const sendTurnBtn = document.getElementById('aegora-send-turn-btn');
        const timerEl = document.getElementById('aegora-call-timer');
        const langBadgeEl = document.getElementById('aegora-call-lang-badge');
        const presetSelect = document.getElementById('aegora-voice-preset-select');

        if (presetSelect) {
            presetSelect.addEventListener('change', () => {
                selectedVoiceOverride = presetSelect.value;
                localStorage.setItem('aegora_voice_override', selectedVoiceOverride);
            });
        }

        overlay.querySelectorAll('.aegora-vlang-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                activeSpokenLang = btn.getAttribute('data-voice-lang') || 'id';
                overlay.querySelectorAll('.aegora-vlang-btn').forEach(b => {
                    b.className = 'aegora-vlang-btn px-2 py-1 rounded-lg font-bold text-stone-600';
                });
                btn.className = 'aegora-vlang-btn px-2 py-1 rounded-lg font-bold bg-[#571724] text-white';
                if (langBadgeEl) langBadgeEl.textContent = `🌐 Auto: ${activeSpokenLang.toUpperCase()}`;
                if (callRecognition) {
                    try { callRecognition.abort(); } catch {}
                }
                if (!isMuted) startCallListening();
            });
        });

        callTimerInterval = setInterval(() => {
            secondsElapsed++;
            const m = String(Math.floor(secondsElapsed / 60)).padStart(2, '0');
            const s = String(secondsElapsed % 60).padStart(2, '0');
            if (timerEl) timerEl.textContent = `${m}:${s}`;
        }, 1000);

        function updateLangBadge(langCode) {
            if (!langCode || !langBadgeEl) return;
            activeSpokenLang = getShortLang(langCode);
            langBadgeEl.textContent = `🌐 Auto: ${activeSpokenLang.toUpperCase()}`;
        }

        function setStatus(state, customText) {
            if (!isCallActive) return;
            orbEl.className = 'aegora-voice-orb ' + state;
            if (state === 'speaking') {
                statusEl.textContent = customText || labels.speaking;
            } else if (state === 'thinking') {
                statusEl.textContent = customText || labels.thinking;
            } else {
                statusEl.textContent = customText || labels.listening;
            }
        }

        function stopActiveRecordingHandles() {
            if (stsRafId) {
                cancelAnimationFrame(stsRafId);
                stsRafId = null;
            }
            if (stsRecorder && stsRecorder.state !== 'inactive') {
                try { stsRecorder.stop(); } catch {}
            }
            stsRecorder = null;
            if (stsStream) {
                stsStream.getTracks().forEach(t => t.stop());
                stsStream = null;
            }
            if (stsAudioContext) {
                try { stsAudioContext.close(); } catch {}
                stsAudioContext = null;
            }
            if (callRecognition) {
                try { callRecognition.abort(); } catch {}
                callRecognition = null;
            }
            if (sendTurnBtn) sendTurnBtn.classList.add('hidden');
        }

        function endCall() {
            if (!isCallActive) return;
            isCallActive = false;

            clearInterval(callTimerInterval);
            document.removeEventListener('keydown', handleEscKey);
            window.removeEventListener('aegora:assistant-reply', handleAssistantReply);

            stopActiveRecordingHandles();
            stopCurrentPlayback();

            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }

        function handleEscKey(e) {
            if (e.key === 'Escape') endCall();
        }
        document.addEventListener('keydown', handleEscKey);
        endCallBtn.addEventListener('click', endCall);

        muteBtn.addEventListener('click', () => {
            isMuted = !isMuted;
            muteLabel.textContent = isMuted ? labels.unmute : labels.mute;
            if (isMuted) {
                muteBtn.classList.add('bg-stone-300');
                stopActiveRecordingHandles();
                setStatus('listening', isEn ? 'Microphone muted' : 'Mikrofon dibisukan');
            } else {
                muteBtn.classList.remove('bg-stone-300');
                startCallListening();
            }
        });

        // Used in fallback Web Speech mode when Deepgram key is not yet entered
        function handleAssistantReply(event) {
            if (!isCallActive || deepgramConfig.configured) return;

            const assistantData = event.detail;
            const rawContent = assistantData?.content || '';
            const cleanContent = stripMarkdown(rawContent);
            const replyLang = assistantData?.detected_language || detectTextLanguage(cleanContent, activeSpokenLang);

            updateLangBadge(replyLang);
            transcriptEl.textContent = cleanContent;
            setStatus('speaking');

            synthesizeAndPlay(cleanContent, replyLang, () => {
                if (isCallActive && !isMuted) {
                    setTimeout(() => {
                        if (isCallActive && !isMuted) startCallListening();
                    }, 450);
                }
            });
        }
        window.addEventListener('aegora:assistant-reply', handleAssistantReply);

        // ---------------------------------------------------------------------
        // Mode A: Full Deepgram STS Loop (MediaRecorder + Silence VAD -> /api/voice/converse)
        // ---------------------------------------------------------------------
        async function startDeepgramStsTurn() {
            if (!isCallActive || isMuted) return;
            setStatus('listening');

            try {
                stsStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const chunks = [];
                const mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : 'audio/webm';
                stsRecorder = new MediaRecorder(stsStream, { mimeType: mime });

                let speechDetected = false;
                let silenceStart = null;
                let turnSubmitted = false;
                const startedAt = Date.now();

                // Voice Activity Detection (VAD) so guest doesn't need to click stop
                stsAudioContext = new (window.AudioContext || window.webkitAudioContext)();
                const source = stsAudioContext.createMediaStreamSource(stsStream);
                const analyser = stsAudioContext.createAnalyser();
                analyser.fftSize = 512;
                source.connect(analyser);
                const dataArray = new Uint8Array(analyser.frequencyBinCount);

                const finalizeTurn = () => {
                    if (turnSubmitted || !isCallActive) return;
                    turnSubmitted = true;
                    if (stsRafId) {
                        cancelAnimationFrame(stsRafId);
                        stsRafId = null;
                    }
                    if (sendTurnBtn) sendTurnBtn.classList.add('hidden');
                    if (stsRecorder && stsRecorder.state !== 'inactive') {
                        try { stsRecorder.stop(); } catch {}
                    }
                };

                if (sendTurnBtn) {
                    sendTurnBtn.classList.remove('hidden');
                    sendTurnBtn.onclick = finalizeTurn;
                }

                const monitorAudioLevel = () => {
                    if (!isCallActive || isMuted || turnSubmitted) return;
                    analyser.getByteTimeDomainData(dataArray);
                    let sumSquares = 0;
                    for (let i = 0; i < dataArray.length; i++) {
                        const v = (dataArray[i] - 128) / 128;
                        sumSquares += v * v;
                    }
                    const rms = Math.sqrt(sumSquares / dataArray.length);
                    const now = Date.now();

                    if (rms > 0.025) {
                        speechDetected = true;
                        silenceStart = null;
                    } else if (speechDetected) {
                        if (!silenceStart) silenceStart = now;
                        // Stop after 1.6s of natural pause following speech
                        if (now - silenceStart > 1600) {
                            finalizeTurn();
                            return;
                        }
                    }

                    // Safety cap at 18 seconds per turn
                    if (now - startedAt > 18000) {
                        finalizeTurn();
                        return;
                    }

                    stsRafId = requestAnimationFrame(monitorAudioLevel);
                };

                stsRecorder.ondataavailable = (e) => {
                    if (e.data && e.data.size > 0) chunks.push(e.data);
                };

                stsRecorder.onstop = async () => {
                    if (stsStream) {
                        stsStream.getTracks().forEach(t => t.stop());
                        stsStream = null;
                    }
                    if (stsAudioContext) {
                        try { stsAudioContext.close(); } catch {}
                        stsAudioContext = null;
                    }

                    if (!isCallActive || isMuted) return;

                    const blob = new Blob(chunks, { type: mime });
                    if (blob.size < 1200 || !speechDetected) {
                        setTimeout(() => {
                            if (isCallActive && !isMuted) startDeepgramStsTurn();
                        }, 300);
                        return;
                    }

                    setStatus('thinking');
                    transcriptEl.textContent = isEn ? 'Transcribing & preparing answer...' : 'Memahami bahasa & menyiapkan jawaban ramah...';

                    try {
                        const fd = new FormData();
                        fd.append('audio', blob, 'guest-turn.webm');
                        const sid = typeof window.aegoraGetSessionId === 'function' ? window.aegoraGetSessionId() : (localStorage.getItem('aegora_session_id') || '');
                        fd.append('session_id', sid);
                        fd.append('locale', activeSpokenLang);
                        if (selectedVoiceOverride) fd.append('voice', selectedVoiceOverride);

                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/voice/converse', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                            body: fd,
                        });
                        const json = await res.json();

                        if (!isCallActive) return;

                        if (json?.status === 'success' && json.data) {
                            const d = json.data;
                            updateLangBadge(d.reply_language || d.detected_language);

                            // Sync voice turn into visible chat stream
                            if (typeof window.aegoraAppendVoiceTurn === 'function') {
                                window.aegoraAppendVoiceTurn(d.user_message, d.assistant_message);
                            }

                            const cleanReply = d.clean_speech_text || stripMarkdown(d.assistant_message?.content || '');
                            transcriptEl.textContent = cleanReply;
                            setStatus('speaking');

                            const resumeNext = () => {
                                if (isCallActive && !isMuted) {
                                    setTimeout(() => {
                                        if (isCallActive && !isMuted) startDeepgramStsTurn();
                                    }, 400);
                                }
                            };

                            if (d.audio?.base64) {
                                playBase64Audio(d.audio.base64, d.audio.content_type, resumeNext);
                            } else {
                                speakWithNonGoogleFallback(cleanReply, d.reply_language || activeSpokenLang, resumeNext);
                            }
                        } else {
                            // Empty speech or fallback
                            if (isCallActive && !isMuted) {
                                setTimeout(() => startDeepgramStsTurn(), 500);
                            }
                        }
                    } catch {
                        if (isCallActive && !isMuted) {
                            transcriptEl.textContent = 'Kendala koneksi sebentar, mendengarkan ulang...';
                            setTimeout(() => startDeepgramStsTurn(), 1000);
                        }
                    }
                };

                stsRecorder.start();
                monitorAudioLevel();
            } catch (err) {
                if (err?.name === 'NotAllowedError') {
                    alert(labels.micDenied);
                    endCall();
                } else {
                    // Fallback to SpeechRecognition if MediaRecorder fails
                    startBrowserSpeechTurn();
                }
            }
        }

        // ---------------------------------------------------------------------
        // Mode B: Fallback Loop (Web Speech Recognition -> Chat API -> Deepgram TTS or Non-Google Voice)
        // ---------------------------------------------------------------------
        function startBrowserSpeechTurn() {
            if (!isCallActive || isMuted || !SpeechRecognition) return;
            setStatus('listening');

            try {
                callRecognition = new SpeechRecognition();
                callRecognition.continuous = false;
                callRecognition.interimResults = true;
                callRecognition.lang = activeSpokenLang === 'en' ? 'en-US' : 'id-ID';

                let finalTranscript = '';

                callRecognition.onresult = (event) => {
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            finalTranscript = transcript;
                        } else {
                            transcriptEl.textContent = `"${transcript}"`;
                        }
                    }
                };

                callRecognition.onerror = (e) => {
                    if (e.error === 'not-allowed') {
                        alert(labels.micDenied);
                        endCall();
                    }
                };

                callRecognition.onend = () => {
                    if (!isCallActive) return;

                    const trimmed = finalTranscript.trim();
                    if (trimmed) {
                        const detected = detectTextLanguage(trimmed, activeSpokenLang);
                        updateLangBadge(detected);
                        transcriptEl.textContent = `"${trimmed}"`;
                        setStatus('thinking');

                        if (typeof window.aegoraSendMessage === 'function') {
                            window.aegoraSendMessage(trimmed, { voiceMode: true, locale: detected });
                        }
                    } else if (!isMuted) {
                        setTimeout(() => {
                            if (isCallActive && !isMuted) startCallListening();
                        }, 450);
                    }
                };

                callRecognition.start();
            } catch {
                endCall();
            }
        }

        function startCallListening() {
            if (!isCallActive || isMuted) return;
            if (deepgramConfig.configured && hasMediaRecorder) {
                startDeepgramStsTurn();
            } else {
                startBrowserSpeechTurn();
            }
        }

        startCallListening();
    }
});
