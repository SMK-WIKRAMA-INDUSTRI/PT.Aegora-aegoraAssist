import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // Session state
    let sessionId = localStorage.getItem('aegora_session_id') || ('aegora_' + Math.random().toString(36).substring(2, 11) + '_' + Date.now());
    localStorage.setItem('aegora_session_id', sessionId);

    // DOM Elements
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-btn');
    const messagesContainer = document.getElementById('messages-container');
    const loadingIndicator = document.getElementById('ai-loading-indicator');
    const newChatBtn = document.getElementById('new-chat-btn');
    const navStaffBtn = document.getElementById('nav-contact-staff-btn');
    const staffModal = document.getElementById('staff-modal');
    const closeStaffModalBtn = document.getElementById('close-staff-modal');
    const requestStaffChatBtn = document.getElementById('request-staff-chat-btn');
    const attachBtn = document.getElementById('attach-btn');
    const voiceBtn = document.getElementById('voice-btn');

    let isProcessing = false;

    // Helper: Scroll smoothly to bottom
    const scrollToBottom = () => {
        setTimeout(() => {
            window.scrollTo({
                top: document.documentElement.scrollHeight,
                behavior: 'smooth'
            });
        }, 80);
    };

    // Helper: Format current time in WIB
    const getCurrentTimeWIB = () => {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes} WIB`;
    };

    // Render User Message Bubble
    const appendUserMessage = (text, time = null) => {
        const timeStr = time || getCurrentTimeWIB();
        const div = document.createElement('div');
        div.className = 'flex justify-end w-full group animate-in fade-in slide-in-from-bottom-2 duration-200';
        div.innerHTML = `
            <div class="max-w-xl flex flex-col items-end">
                <div class="bg-[#F8F2E4] border border-[#E7DDC5] rounded-2xl rounded-tr-xs px-5 py-3.5 shadow-2xs text-stone-800 text-xs sm:text-sm leading-relaxed">
                    <p class="whitespace-pre-wrap">${escapeHtml(text)}</p>
                </div>
                <div class="flex items-center gap-1.5 mt-1 mr-1 text-[11px] text-stone-400">
                    <span>${timeStr}</span>
                    <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        <path d="M12.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-1-1a1 1 0 011.414-1.414L7 11.586l4.293-4.293a1 1 0 011.414 0z"/>
                    </svg>
                </div>
            </div>
        `;
        messagesContainer.appendChild(div);
        scrollToBottom();
    };

    // Render AI Message Bubble
    const appendAssistantMessage = (data, time = null) => {
        const timeStr = time || getCurrentTimeWIB();
        const contentHtml = data.rendered_html || escapeHtml(data.content || '').replace(/\n/g, '<br>');
        const facility = data.facility;

        const div = document.createElement('div');
        div.className = 'flex items-start gap-3 w-full group animate-in fade-in slide-in-from-bottom-2 duration-200';

        let facilityHtml = '';
        if (facility) {
            let tagsHtml = '';
            if (facility.tags && facility.tags.length) {
                tagsHtml = `
                    <div class="p-3.5 flex flex-wrap items-center gap-2 border-b border-[#EBE1CD] bg-[#F7F1E1]">
                        ${facility.tags.map(t => `
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-[#4A1521] bg-[#EFE5D3] border border-[#DFCFA8] px-2.5 py-1 rounded-full">
                                <span class="text-amber-700">✦</span>
                                ${escapeHtml(t)}
                            </span>
                        `).join('')}
                    </div>
                `;
            }

            let actionsHtml = '';
            if (facility.actions && facility.actions.length) {
                actionsHtml = `
                    <div class="p-3.5 flex flex-wrap gap-2">
                        ${facility.actions.map(act => {
                            if (act.primary) {
                                return `
                                    <button type="button" data-action="${escapeHtml(act.action || '')}" class="facility-action-btn flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#571724] hover:bg-[#46111C] text-white text-xs font-semibold shadow-xs transition active:scale-95">
                                        <span>📖</span>
                                        <span>${escapeHtml(act.label)}</span>
                                    </button>
                                `;
                            } else {
                                const icon = act.action === 'reception' ? '🛎️' : '🍽️';
                                return `
                                    <button type="button" data-action="${escapeHtml(act.action || '')}" class="facility-action-btn flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#F0E6D2] hover:bg-[#E7DBC2] text-[#4A1521] border border-[#DFCFA8] text-xs font-semibold transition active:scale-95">
                                        <span>${icon}</span>
                                        <span>${escapeHtml(act.label)}</span>
                                    </button>
                                `;
                            }
                        }).join('')}
                    </div>
                `;
            }

            facilityHtml = `
                <div class="mt-4 rounded-2xl overflow-hidden border border-[#E4D9C1] bg-[#FAF5E8] shadow-xs">
                    <div class="relative h-48 sm:h-56 w-full overflow-hidden bg-stone-900">
                        <img src="${facility.image}" alt="${escapeHtml(facility.title)}" class="w-full h-full object-cover opacity-90 hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>
                        <div class="absolute bottom-3 left-4 right-4 flex flex-col sm:flex-row sm:items-end justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-semibold text-amber-300 tracking-wider uppercase bg-black/40 px-2 py-0.5 rounded-sm">
                                    ${escapeHtml(facility.location || '')}
                                </span>
                                <h3 class="font-serif text-lg sm:text-xl font-bold text-white drop-shadow-sm mt-0.5">
                                    ${escapeHtml(facility.title)}
                                </h3>
                            </div>
                            <span class="self-start sm:self-end text-[11px] font-medium text-stone-200 bg-white/20 backdrop-blur-xs px-2.5 py-1 rounded-full border border-white/20">
                                🕒 ${escapeHtml(facility.hours || '')}
                            </span>
                        </div>
                    </div>
                    ${tagsHtml}
                    ${actionsHtml}
                </div>
            `;
        }

        div.innerHTML = `
            <div class="w-8 h-8 rounded-xl bg-[#571724] text-amber-200 flex items-center justify-center shadow-xs shrink-0 mt-0.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="flex-1 max-w-3xl space-y-3">
                <div class="bg-[#FDFBF7] border border-[#E8DFCB] rounded-2xl rounded-tl-xs p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-[#F0E8D7] pb-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-[#3B121C]">Aegora Assist</span>
                            <span class="text-[10px] text-stone-400 font-medium">• AI Concierge Resmi</span>
                        </div>
                        <span class="text-[11px] text-stone-400">${timeStr}</span>
                    </div>

                    <div class="prose prose-stone text-xs sm:text-sm text-stone-700 leading-relaxed max-w-none">
                        ${contentHtml}
                    </div>

                    ${facilityHtml}

                    <div class="pt-2 flex items-center justify-between border-t border-[#F2EADB] text-[11px] text-stone-400">
                        <span>Apakah informasi ini membantu Anda?</span>
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
        `;

        messagesContainer.appendChild(div);
        scrollToBottom();
    };

    // Helper: HTML escaping
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Send Message Handler
    const handleSendMessage = async (text) => {
        const query = (text || chatInput.value).trim();
        if (!query || isProcessing) return;

        isProcessing = true;
        chatInput.value = '';
        chatInput.disabled = true;
        sendBtn.disabled = true;

        // Render user message immediately
        appendUserMessage(query);

        // Show loading indicator
        loadingIndicator.classList.remove('hidden');
        scrollToBottom();

        try {
            const response = await window.axios.post('/api/chat/send', {
                message: query,
                session_id: sessionId
            });

            if (response.data && response.data.status === 'success') {
                const assistantData = response.data.data.assistant_message;
                appendAssistantMessage(assistantData);
            } else {
                throw new Error('Respon tidak valid dari server');
            }
        } catch (error) {
            console.error('Chat send error:', error);
            appendAssistantMessage({
                content: 'Mohon maaf, saat ini sistem concierge sedang mengalami sedikit kendala jaringan. Silakan hubungi langsung Front Desk di **ext. 0** atau coba beberapa saat lagi.',
                rendered_html: '<p>Mohon maaf, saat ini sistem concierge sedang mengalami sedikit kendala jaringan. Silakan hubungi langsung Front Desk di <strong>ext. 0</strong> atau coba beberapa saat lagi.</p>',
                facility: null
            });
        } finally {
            loadingIndicator.classList.add('hidden');
            chatInput.disabled = false;
            sendBtn.disabled = false;
            chatInput.focus();
            isProcessing = false;
            scrollToBottom();
        }
    };

    // Submit form listener
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleSendMessage();
    });

    // Enter key submit
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    });

    // Click handler for Quick Reply Chips and Service Cards
    document.addEventListener('click', (e) => {
        const target = e.target.closest('[data-quick-prompt]');
        if (target) {
            const prompt = target.getAttribute('data-quick-prompt');
            if (prompt) {
                handleSendMessage(prompt);
            }
            return;
        }

        // Facility Action Buttons
        const actionBtn = e.target.closest('.facility-action-btn');
        if (actionBtn) {
            const action = actionBtn.getAttribute('data-action');
            if (action === 'menu') {
                handleSendMessage('Bisa lihat menu lengkap di The Verandah Restaurant dan daftar harganya?');
            } else if (action === 'breakfast') {
                handleSendMessage('Bagaimana cara reservasi meja sarapan pagi untuk 2 orang besok?');
            } else if (action === 'reception') {
                openStaffModal();
            }
            return;
        }

        // Feedback thumbs up / down
        const fbBtn = e.target.closest('.feedback-btn');
        if (fbBtn) {
            fbBtn.classList.toggle('text-[#571724]');
            fbBtn.classList.toggle('font-bold');
            const parent = fbBtn.parentElement;
            parent.innerHTML = `<span class="text-[11px] text-emerald-700 font-medium">✓ Terima kasih atas masukannya!</span>`;
            return;
        }
    });

    // Modal Staff Handlers
    const openStaffModal = () => {
        staffModal.classList.remove('hidden');
    };

    const closeStaffModal = () => {
        staffModal.classList.add('hidden');
    };

    if (navStaffBtn) navStaffBtn.addEventListener('click', openStaffModal);
    if (closeStaffModalBtn) closeStaffModalBtn.addEventListener('click', closeStaffModal);

    staffModal.addEventListener('click', (e) => {
        if (e.target === staffModal) closeStaffModal();
    });

    if (requestStaffChatBtn) {
        requestStaffChatBtn.addEventListener('click', () => {
            closeStaffModal();
            handleSendMessage('Halo, saya ingin meminta staf resepsionis atau duty manager untuk menghubungi kamar saya.');
        });
    }

    // New Chat Button Handler
    if (newChatBtn) {
        newChatBtn.addEventListener('click', async () => {
            if (confirm('Mulai sesi percakapan baru?')) {
                sessionId = 'aegora_' + Math.random().toString(36).substring(2, 11) + '_' + Date.now();
                localStorage.setItem('aegora_session_id', sessionId);
                
                // Clear extra messages and keep only the welcome card & initial demo messages or reset
                window.location.reload();
            }
        });
    }

    // Attachment & Mic info toasts
    if (attachBtn) {
        attachBtn.addEventListener('click', () => {
            alert('Fitur unggah dokumen/foto reservasi siap diaktifkan saat check-in e-voucher.');
        });
    }

    if (voiceBtn) {
        voiceBtn.addEventListener('click', () => {
            alert('Fitur perintah suara (voice message) sedang disiapkan untuk pembaruan berikutnya.');
        });
    }
});
