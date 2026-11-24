const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

function getActiveLocale() {
    const lang = localStorage.getItem('aegora_lang') || 'id';
    return lang === 'en' ? 'en-US' : 'id-ID';
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
        .replace(/\s+/g, ' ')
        .trim();
}

document.addEventListener('DOMContentLoaded', () => {
    const micBtn = document.getElementById('voice-btn');
    const chatInput = document.getElementById('chat-input');
    const voiceCallBtn = document.getElementById('voice-call-btn');

    let micRecognition = null;
    let isMicRecording = false;

    if (micBtn && chatInput) {
        if (!SpeechRecognition) {
            micBtn.style.display = 'none';
        } else {
            micBtn.addEventListener('click', () => {
                if (isMicRecording) {
                    stopMicRecognition();
                } else {
                    startMicRecognition();
                }
            });
        }
    }

    function startMicRecognition() {
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

            micRecognition.onerror = () => {
                stopMicRecognition();
            };

            micRecognition.onend = () => {
                stopMicRecognition();
            };

            micRecognition.start();
        } catch {
            stopMicRecognition();
        }
    }

    function stopMicRecognition() {
        isMicRecording = false;
        if (micBtn) {
            micBtn.classList.remove('aegora-mic-recording');
        }
        if (micRecognition) {
            try {
                micRecognition.stop();
            } catch {}
            micRecognition = null;
        }
    }

    if (voiceCallBtn) {
        voiceCallBtn.addEventListener('click', () => {
            initiateVoiceCall();
        });
    }

    function initiateVoiceCall() {
        if (!SpeechRecognition) {
            const isEn = (localStorage.getItem('aegora_lang') || 'id') === 'en';
            alert(isEn ? 'Speech recognition is not supported in this browser.' : 'Browser Anda tidak mendukung fitur pengenalan suara.');
            return;
        }

        stopMicRecognition();
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }

        let isCallActive = true;
        let isMuted = false;
        let callRecognition = null;
        let callTimerInterval = null;
        let secondsElapsed = 0;

        const isEn = (localStorage.getItem('aegora_lang') || 'id') === 'en';
        const labels = {
            listening: isEn ? 'Listening...' : 'Mendengarkan...',
            thinking: isEn ? 'Aegora Assist is thinking...' : 'Aegora Assist sedang berpikir...',
            speaking: isEn ? 'Speaking...' : 'Berbicara...',
            endCall: isEn ? 'End Call' : 'Akhiri Panggilan',
            mute: isEn ? 'Mute' : 'Bisukan',
            unmute: isEn ? 'Unmute' : 'Aktifkan Suara',
            micDenied: isEn ? 'Microphone access denied.' : 'Izin mikrofon ditolak.'
        };

        const overlay = document.createElement('div');
        overlay.className = 'aegora-voice-overlay';
        overlay.innerHTML = `
            <div class="w-full max-w-lg flex items-center justify-between border-b border-[#DFCFA8] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl overflow-hidden bg-[#571724]">
                        <img src="/images/aegora-logo.jpg" alt="Aegora" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h3 class="font-serif font-bold text-sm text-[#3B121C]">Aegora Assist Voice Call</h3>
                        <p class="text-[11px] text-stone-500" id="aegora-call-timer">00:00</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#EFE5D3] text-[#571724] border border-[#DFCFA8]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>HD Voice</span>
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

                <div class="space-y-2">
                    <p class="text-sm font-bold text-[#571724] tracking-wide" id="aegora-call-status">${labels.listening}</p>
                    <p class="text-xs text-stone-600 italic min-h-[3rem] px-3" id="aegora-call-transcript"></p>
                </div>
            </div>

            <div class="w-full max-w-sm flex items-center justify-center gap-6 pt-4 border-t border-[#DFCFA8]">
                <button type="button" id="aegora-mute-btn" class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-[#EFE5D3] hover:bg-[#E5D7BF] text-stone-700 transition active:scale-95 text-xs font-semibold">
                    <svg class="w-5 h-5 text-[#571724]" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="aegora-mute-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span id="aegora-mute-label">${labels.mute}</span>
                </button>

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
        const timerEl = document.getElementById('aegora-call-timer');

        callTimerInterval = setInterval(() => {
            secondsElapsed++;
            const m = String(Math.floor(secondsElapsed / 60)).padStart(2, '0');
            const s = String(secondsElapsed % 60).padStart(2, '0');
            if (timerEl) timerEl.textContent = `${m}:${s}`;
        }, 1000);

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

        function endCall() {
            if (!isCallActive) return;
            isCallActive = false;

            clearInterval(callTimerInterval);
            document.removeEventListener('keydown', handleEscKey);
            window.removeEventListener('aegora:assistant-reply', handleAssistantReply);

            if (callRecognition) {
                try {
                    callRecognition.abort();
                } catch {}
                callRecognition = null;
            }

            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }

            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }

        function handleEscKey(e) {
            if (e.key === 'Escape') {
                endCall();
            }
        }
        document.addEventListener('keydown', handleEscKey);

        endCallBtn.addEventListener('click', endCall);

        muteBtn.addEventListener('click', () => {
            isMuted = !isMuted;
            muteLabel.textContent = isMuted ? labels.unmute : labels.mute;
            if (isMuted) {
                muteBtn.classList.add('bg-stone-300');
                if (callRecognition) {
                    try { callRecognition.abort(); } catch {}
                }
                setStatus('listening', isEn ? 'Microphone muted' : 'Mikrofon dibisukan');
            } else {
                muteBtn.classList.remove('bg-stone-300');
                startCallListening();
            }
        });

        function handleAssistantReply(event) {
            if (!isCallActive) return;

            const assistantData = event.detail;
            const rawContent = assistantData?.content || '';
            const cleanContent = stripMarkdown(rawContent);

            transcriptEl.textContent = cleanContent;
            speakReply(cleanContent);
        }
        window.addEventListener('aegora:assistant-reply', handleAssistantReply);

        function startCallListening() {
            if (!isCallActive || isMuted) return;

            setStatus('listening');

            try {
                callRecognition = new SpeechRecognition();
                callRecognition.continuous = false;
                callRecognition.interimResults = true;
                callRecognition.lang = getActiveLocale();

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
                        transcriptEl.textContent = `"${trimmed}"`;
                        setStatus('thinking');

                        if (typeof window.aegoraSendMessage === 'function') {
                            window.aegoraSendMessage(trimmed);
                        }
                    } else if (!isMuted) {
                        setTimeout(() => {
                            if (isCallActive && !isMuted) startCallListening();
                        }, 400);
                    }
                };

                callRecognition.start();
            } catch {
                endCall();
            }
        }

        function speakReply(text) {
            if (!isCallActive || !('speechSynthesis' in window)) {
                setTimeout(() => {
                    if (isCallActive) startCallListening();
                }, 1000);
                return;
            }

            setStatus('speaking');
            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = getActiveLocale();
            utterance.rate = 1.0;
            utterance.pitch = 1.0;

            const voices = window.speechSynthesis.getVoices();
            const targetPrefix = utterance.lang.slice(0, 2);
            const matchedVoice = voices.find(v => v.lang && v.lang.startsWith(targetPrefix));
            if (matchedVoice) {
                utterance.voice = matchedVoice;
            }

            utterance.onend = () => {
                if (isCallActive) {
                    setTimeout(() => {
                        if (isCallActive && !isMuted) {
                            startCallListening();
                        }
                    }, 500);
                }
            };

            utterance.onerror = () => {
                if (isCallActive) {
                    setTimeout(() => {
                        if (isCallActive && !isMuted) {
                            startCallListening();
                        }
                    }, 500);
                }
            };

            window.speechSynthesis.speak(utterance);
        }

        startCallListening();
    }
});
