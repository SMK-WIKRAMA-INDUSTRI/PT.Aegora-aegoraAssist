import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // Session state
    let sessionId = localStorage.getItem('aegora_session_id') || ('aegora_' + Math.random().toString(36).substring(2, 11) + '_' + Date.now());
    localStorage.setItem('aegora_session_id', sessionId);

    // Language state
    let currentLang = localStorage.getItem('aegora_lang') || 'id';

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
    const langIdBtn = document.getElementById('lang-id-btn');
    const langEnBtn = document.getElementById('lang-en-btn');
    const quickReplyContainer = document.getElementById('quick-reply-container');

    let isProcessing = false;

    // Bilingual Dictionaries
    const i18n = {
        id: {
            nav_chat: 'Percakapan',
            nav_staff: 'Hubungi Staf',
            sub_title: 'Aegora Assist Concierge',
            sub_desc: 'Online • Respon instan dalam hitungan detik | PT Aegora Hospitality',
            verified_guest: 'Terverifikasi Tamu',
            welcome_tag: 'LAYANAN KERAMAHAN DIGITAL',
            welcome_title: 'Selamat Datang di Aegora Hotel',
            welcome_desc: 'Saya <strong class="text-[#3B121C] font-semibold">Aegora Assist</strong>, asisten virtual pribadi Anda. Ada yang bisa saya bantu terkait reservasi kamar, jadwal sarapan, fasilitas spa, atau panduan menginap hari ini?',
            recommended_title: 'PERTANYAAN REKOMENDASI',
            input_placeholder: 'Tanyakan apa saja seputar reservasi, fasilitas, atau check-in...',
            security_footer: 'Aegora Assist didukung data resmi PT Aegora • Enkripsi percakapan privat tamu hotel • Hubungi Front Desk ext. 0',
            loading_text: 'Aegora Assist sedang mengetik...',
            modal_staff_title: 'Hubungi Tim Resepsionis',
            modal_staff_sub: 'Front Desk & Concierge Siaga 24/7',
            modal_staff_desc: 'Anda dapat langsung tersambung dengan staf hotel kami melalui saluran cepat berikut:',
            modal_intercom_title: 'Intercom Kamar',
            modal_intercom_desc: 'Tekan angka 0 untuk Front Desk',
            modal_wa_desc: '+62 812-3456-7890 (Respon Cepat)',
            modal_request_title: 'Minta Panggilan Staf ke Kamar',
            modal_request_sub: 'Kirim notifikasi ke Duty Manager',
            modal_send_btn: 'Kirim →',
            error_network: 'Mohon maaf, saat ini sistem concierge sedang mengalami kendala jaringan. Silakan hubungi langsung Front Desk di **ext. 0** atau coba beberapa saat lagi.',
            confirm_new_chat: 'Mulai sesi percakapan baru?',
            feedback_thank_you: '✓ Terima kasih atas masukannya!',
            feedback_helpful: 'Apakah informasi ini membantu Anda?',
            cards: {
                room: {
                    title: 'Pesan & Cek Kamar',
                    desc: 'Ketersediaan, tipe Deluxe/Executive, tarif malam',
                    prompt: 'Apakah tersedia kamar Deluxe atau Executive untuk malam ini?'
                },
                facility: {
                    title: 'Fasilitas & Jadwal',
                    desc: 'The Verandah, Infinity Pool Lt. 3, Spa, Gym',
                    prompt: 'Jam berapa sarapan dibuka dan apa saja fasilitas yang tersedia di hotel?'
                },
                checkin: {
                    title: 'Check-in & Tagihan',
                    desc: 'Panduan 14.00 WIB, deposit, rincian e-folio',
                    prompt: 'Bagaimana prosedur check-in, deposit, dan rincian e-folio tagihan?'
                },
                staff: {
                    title: 'Hubungi Staf Langsung',
                    desc: 'Sambungkan ke Resepsionis & Concierge hotel',
                    prompt: 'Bagaimana cara menghubungi resepsionis hotel secara langsung?'
                }
            },
            quickReplies: [
                { icon: '🗝️', text: 'Bagaimana cara check-out ekspres?', prompt: 'Bagaimana cara check-out ekspres tanpa antre di front desk?' },
                { icon: '🚐', text: 'Jadwal airport shuttle', prompt: 'Berapa jadwal airport shuttle dari hotel ke bandara hari ini?' },
                { icon: '💆', text: 'Paket aromaterapi spa', prompt: 'Apa saja pilihan paket aromaterapi spa dan berapa tarifnya?' },
                { icon: '📶', text: 'Password Wi-Fi hotel', prompt: 'Berapa nama jaringan dan password Wi-Fi hotel?' }
            ]
        },
        en: {
            nav_chat: 'Conversations',
            nav_staff: 'Contact Staff',
            sub_title: 'Aegora Assist Concierge',
            sub_desc: 'Online • Instant response in seconds | PT Aegora Hospitality',
            verified_guest: 'Verified Guest',
            welcome_tag: 'DIGITAL HOSPITALITY SERVICE',
            welcome_title: 'Welcome to Aegora Hotel',
            welcome_desc: 'I am <strong class="text-[#3B121C] font-semibold">Aegora Assist</strong>, your personal AI concierge. How may I assist you with room reservations, dining schedules, spa facilities, or hotel guides today?',
            recommended_title: 'RECOMMENDED QUESTIONS',
            input_placeholder: 'Ask anything about room reservations, facilities, or check-in...',
            security_footer: 'Aegora Assist powered by official Aegora data • Private guest encryption • Contact Front Desk ext. 0',
            loading_text: 'Aegora Assist is typing...',
            modal_staff_title: 'Contact Front Desk Team',
            modal_staff_sub: 'Front Desk & Concierge on Duty 24/7',
            modal_staff_desc: 'You can connect directly with our hotel team through the following channels:',
            modal_intercom_title: 'In-Room Intercom',
            modal_intercom_desc: 'Dial 0 for Front Desk',
            modal_wa_desc: '+62 812-3456-7890 (Quick Response)',
            modal_request_title: 'Request Staff Call to Room',
            modal_request_sub: 'Dispatch request to Duty Manager',
            modal_send_btn: 'Send →',
            error_network: 'We apologize, but our concierge service is experiencing a temporary network delay. Please dial **ext. 0** directly for the Front Desk or try again in a moment.',
            confirm_new_chat: 'Start a new conversation session?',
            feedback_thank_you: '✓ Thank you for your feedback!',
            feedback_helpful: 'Was this information helpful?',
            cards: {
                room: {
                    title: 'Book & Check Rooms',
                    desc: 'Availability, Deluxe/Executive suites, rates',
                    prompt: 'Are there Deluxe or Executive rooms available for tonight?'
                },
                facility: {
                    title: 'Facilities & Hours',
                    desc: 'The Verandah, Level 3 Infinity Pool, Spa, Gym',
                    prompt: 'What time is breakfast served and what facilities are available?'
                },
                checkin: {
                    title: 'Check-in & Billing',
                    desc: '14:00 guide, room deposit, e-folio billing',
                    prompt: 'What are the check-in procedure, deposit, and e-folio details?'
                },
                staff: {
                    title: 'Contact Staff Directly',
                    desc: 'Connect to 24/7 Front Desk & Concierge',
                    prompt: 'How can I directly contact the front desk staff or concierge team?'
                }
            },
            quickReplies: [
                { icon: '🗝️', text: 'How does express check-out work?', prompt: 'How does express check-out work without waiting at the front desk?' },
                { icon: '🚐', text: 'Airport shuttle schedule', prompt: 'What is the airport shuttle schedule from the hotel today?' },
                { icon: '💆', text: 'Spa aromatherapy packages', prompt: 'What spa aromatherapy packages are available and what are the rates?' },
                { icon: '📶', text: 'Hotel Wi-Fi password', prompt: 'What is the network name and password for hotel Wi-Fi?' }
            ]
        }
    };

    // Apply Language Function
    const applyLanguage = (lang) => {
        currentLang = lang;
        localStorage.setItem('aegora_lang', lang);

        const dict = i18n[lang] || i18n.id;

        // 1. Update Language Switcher UI Active Class
        if (langIdBtn && langEnBtn) {
            if (lang === 'id') {
                langIdBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-[#571724] font-bold bg-[#FAF5E8] shadow-xs';
                langEnBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-stone-500 hover:text-stone-800';
            } else {
                langEnBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-[#571724] font-bold bg-[#FAF5E8] shadow-xs';
                langIdBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-stone-500 hover:text-stone-800';
            }
        }

        // 2. Translate text elements by [data-i18n]
        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            if (dict[key]) {
                el.innerHTML = dict[key];
            }
        });

        // 3. Translate placeholder attributes
        document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (dict[key]) {
                el.setAttribute('placeholder', dict[key]);
            }
        });

        // 4. Update Service Cards
        document.querySelectorAll('.service-card-btn').forEach((card) => {
            const key = card.getAttribute('data-card-key');
            if (key && dict.cards && dict.cards[key]) {
                const cardData = dict.cards[key];
                const titleEl = card.querySelector('.service-card-title');
                const descEl = card.querySelector('.service-card-desc');
                if (titleEl) titleEl.textContent = cardData.title;
                if (descEl) descEl.textContent = cardData.desc;
                card.setAttribute('data-quick-prompt', cardData.prompt);
            }
        });

        // 5. Update Quick Reply Chips
        if (quickReplyContainer && dict.quickReplies) {
            quickReplyContainer.innerHTML = dict.quickReplies.map((q) => `
                <button type="button" 
                        data-quick-prompt="${escapeHtml(q.prompt)}" 
                        class="quick-reply-btn shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#FAF5E8] hover:bg-[#F0E6D2] active:scale-95 border border-[#DFCFA8] text-stone-700 font-medium transition shadow-2xs">
                    <span>${q.icon}</span>
                    <span>${escapeHtml(q.text)}</span>
                </button>
            `).join('');
        }
    };

    // Attach Language Switcher Click Handlers
    if (langIdBtn) {
        langIdBtn.addEventListener('click', () => applyLanguage('id'));
    }
    if (langEnBtn) {
        langEnBtn.addEventListener('click', () => applyLanguage('en'));
    }

    // Initialize Language from preference
    applyLanguage(currentLang);

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
        const dict = i18n[currentLang] || i18n.id;

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
                            }
                            return `
                                <button type="button" data-action="${escapeHtml(act.action || '')}" class="facility-action-btn px-3.5 py-2 rounded-xl bg-[#EFE5D3] hover:bg-[#E5D7BF] text-stone-700 text-xs font-medium transition active:scale-95">
                                    <span>${escapeHtml(act.label)}</span>
                                </button>
                            `;
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
            <div class="w-8 h-8 rounded-xl overflow-hidden border border-[#571724]/20 shadow-xs shrink-0 mt-0.5 bg-[#350711]">
                <img src="/images/aegora-logo.jpg" alt="Aegora Assist" class="w-full h-full object-cover">
            </div>
            <div class="flex-1 max-w-3xl space-y-3">
                <div class="bg-[#FDFBF7] border border-[#E8DFCB] rounded-2xl rounded-tl-xs p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-[#F0E8D7] pb-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-[#3B121C]">Aegora Assist</span>
                            <span class="text-[10px] text-stone-400 font-medium">• ${currentLang === 'en' ? 'Official AI Concierge' : 'AI Concierge Resmi'}</span>
                        </div>
                        <span class="text-[11px] text-stone-400">${timeStr}</span>
                    </div>

                    <div class="prose prose-stone text-xs sm:text-sm text-stone-700 leading-relaxed max-w-none">
                        ${contentHtml}
                    </div>

                    ${facilityHtml}

                    <div class="pt-2 flex items-center justify-between border-t border-[#F2EADB] text-[11px] text-stone-400">
                        <span>${dict.feedback_helpful}</span>
                        <div class="flex items-center gap-2">
                            <button type="button" class="feedback-btn hover:text-stone-700 p-1 rounded-md hover:bg-[#EFE8D6] transition" title="Helpful">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                            </button>
                            <button type="button" class="feedback-btn hover:text-stone-700 p-1 rounded-md hover:bg-[#EFE8D6] transition" title="Not Helpful">
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
                session_id: sessionId,
                locale: currentLang
            });

            if (response.data && response.data.status === 'success') {
                const assistantData = response.data.data.assistant_message;
                appendAssistantMessage(assistantData);
            } else {
                throw new Error('Respon tidak valid dari server');
            }
        } catch (error) {
            console.error('Chat send error:', error);
            const dict = i18n[currentLang] || i18n.id;
            appendAssistantMessage({
                content: dict.error_network,
                rendered_html: `<p>${dict.error_network}</p>`,
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
                const prompt = currentLang === 'en'
                    ? 'Could you provide the full dining menu and prices at The Verandah Restaurant?'
                    : 'Bisa lihat menu lengkap di The Verandah Restaurant dan daftar harganya?';
                handleSendMessage(prompt);
            } else if (action === 'breakfast') {
                const prompt = currentLang === 'en'
                    ? 'How can I reserve a breakfast table for 2 guests tomorrow morning?'
                    : 'Bagaimana cara reservasi meja sarapan pagi untuk 2 orang besok?';
                handleSendMessage(prompt);
            } else if (action === 'reception') {
                openStaffModal();
            }
            return;
        }

        // Feedback thumbs up / down
        const fbBtn = e.target.closest('.feedback-btn');
        if (fbBtn) {
            const dict = i18n[currentLang] || i18n.id;
            const parent = fbBtn.parentElement;
            parent.innerHTML = `<span class="text-[11px] text-emerald-700 font-medium">${dict.feedback_thank_you}</span>`;
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
            const prompt = currentLang === 'en'
                ? 'Hello, I would like to request hotel front desk staff or the duty manager to call my room.'
                : 'Halo, saya ingin meminta staf resepsionis atau duty manager untuk menghubungi kamar saya.';
            handleSendMessage(prompt);
        });
    }

    // New Chat Button Handler
    if (newChatBtn) {
        newChatBtn.addEventListener('click', async () => {
            const dict = i18n[currentLang] || i18n.id;
            if (confirm(dict.confirm_new_chat)) {
                sessionId = 'aegora_' + Math.random().toString(36).substring(2, 11) + '_' + Date.now();
                localStorage.setItem('aegora_session_id', sessionId);
                window.location.reload();
            }
        });
    }

    // Attachment & Mic info toasts
    if (attachBtn) {
        attachBtn.addEventListener('click', () => {
            alert(currentLang === 'en'
                ? 'Document / photo upload feature will be available for online voucher check-in.'
                : 'Fitur unggah dokumen/foto reservasi siap diaktifkan saat check-in e-voucher.');
        });
    }

    if (voiceBtn) {
        voiceBtn.addEventListener('click', () => {
            alert(currentLang === 'en'
                ? 'Voice message feature is currently in preparation for upcoming update.'
                : 'Fitur perintah suara (voice message) sedang disiapkan untuk pembaruan berikutnya.');
        });
    }
});
