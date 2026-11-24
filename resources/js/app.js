import './bootstrap';
import './aegora-voice';

document.addEventListener('DOMContentLoaded', () => {
    // Session state
    const urlParams = new URLSearchParams(window.location.search);
    const urlSessionId = urlParams.get('session_id');
    let sessionId = urlSessionId || localStorage.getItem('aegora_session_id') || ('aegora_' + Math.random().toString(36).substring(2, 11) + '_' + Date.now());
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
    const pdfFileInput = document.getElementById('pdf-file-input');
    const voiceBtn = document.getElementById('voice-btn');
    const langIdBtn = document.getElementById('lang-id-btn');
    const langEnBtn = document.getElementById('lang-en-btn');
    const quickReplyWrapper = document.getElementById('quick-reply-wrapper');
    const quickReplyContainer = document.getElementById('quick-reply-container');
    const bookingModal = document.getElementById('booking-modal');
    const closeBookingModalBtn = document.getElementById('close-booking-modal');
    const bookingForm = document.getElementById('booking-form');

    let isProcessing = false;
    let hasUserMessaged = false;

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
            loading_text: 'Aegora Assist sedang merespons...',
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
                    prompt: 'Saya ingin memesan kamar untuk menginap'
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
                { icon: '🧹', text: 'Minta 2 handuk ke kamar 304', prompt: 'Minta tambahan 2 handuk dan sikat gigi ke kamar 304' },
                { icon: '🛎️', text: 'Pesan Kamar Hotel', prompt: 'Saya ingin pesan kamar hotel sekarang' },
                { icon: '🍽️', text: 'Pesan Nasi Goreng ke kamar', prompt: 'Pesan 1 Nasi Goreng dan Es Teh ke kamar 201' },
                { icon: '🎉', text: 'Rencana Honeymoon / Ulang Tahun', prompt: 'Saya menginap besok untuk merayakan ulang tahun istri saya' }
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
            loading_text: 'Aegora Assist is responding...',
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
                    prompt: 'I would like to book a hotel room'
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
                { icon: '🧹', text: 'Send towels to room 304', prompt: 'Please send 2 extra towels and toothbrushes to room 304' },
                { icon: '🛎️', text: 'Book a Hotel Room', prompt: 'I want to book a hotel room now' },
                { icon: '🍽️', text: 'Room service dining', prompt: 'Order 1 Fried Rice and Iced Tea to room 201' },
                { icon: '🎉', text: 'Anniversary celebration', prompt: 'I am staying tomorrow to celebrate my wife birthday' }
            ]
        }
    };

    // Apply Language Function
    const applyLanguage = (lang) => {
        currentLang = lang;
        localStorage.setItem('aegora_lang', lang);

        const dict = i18n[lang] || i18n.id;

        // Update Switcher UI
        if (langIdBtn && langEnBtn) {
            if (lang === 'id') {
                langIdBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-[#571724] font-bold bg-[#FAF5E8] shadow-xs';
                langEnBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-stone-500 hover:text-stone-800';
            } else {
                langEnBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-[#571724] font-bold bg-[#FAF5E8] shadow-xs';
                langIdBtn.className = 'px-2.5 py-1 rounded-lg transition-all text-stone-500 hover:text-stone-800';
            }
        }

        // Translate text elements
        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            if (dict[key]) el.innerHTML = dict[key];
        });

        // Translate placeholders
        document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (dict[key]) el.setAttribute('placeholder', dict[key]);
        });

        // Update Service Cards
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

        // Update Quick Reply Chips
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

    if (langIdBtn) langIdBtn.addEventListener('click', () => applyLanguage('id'));
    if (langEnBtn) langEnBtn.addEventListener('click', () => applyLanguage('en'));
    applyLanguage(currentLang);

    // Function to hide quick reply recommendations once a message has been sent
    const hideQuickReplies = () => {
        if (quickReplyWrapper && !hasUserMessaged) {
            hasUserMessaged = true;
            quickReplyWrapper.style.opacity = '0';
            quickReplyWrapper.style.transform = 'translateY(10px)';
            setTimeout(() => {
                quickReplyWrapper.classList.add('hidden');
            }, 300);
        }
    };

    // Scroll helper
    const scrollToBottom = () => {
        setTimeout(() => {
            window.scrollTo({
                top: document.documentElement.scrollHeight,
                behavior: 'smooth'
            });
        }, 80);
    };

    const getCurrentTimeWIB = () => {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes} WIB`;
    };

    // Render User Bubble
    const appendUserMessage = (text, time = null) => {
        hideQuickReplies();
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

    // Render Assistant Bubble
    const appendAssistantMessage = (data, time = null) => {
        const timeStr = time || getCurrentTimeWIB();
        const contentHtml = data.rendered_html || escapeHtml(data.content || '').replace(/\n/g, '<br>');
        const facility = data.facility;
        const specialAction = data.special_action;
        const dict = i18n[currentLang] || i18n.id;

        // Check if action triggers booking form
        if (specialAction && specialAction.type === 'show_booking_form') {
            setTimeout(() => {
                openBookingModal();
            }, 600);
        }

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

        // Special Booking Action CTA
        let bookingCtaHtml = '';
        if (specialAction && specialAction.type === 'show_booking_form') {
            bookingCtaHtml = `
                <div class="mt-3 p-3 bg-[#F4EDE0] border border-[#DFCFA8] rounded-2xl flex items-center justify-between">
                    <div>
                        <p class="font-bold text-xs text-[#3B121C]">Formulir Reservasi Tamu Siap</p>
                        <p class="text-[10px] text-stone-500">Isi data kamar dan tanggal menginap Anda</p>
                    </div>
                    <button type="button" class="open-booking-btn px-3.5 py-1.5 rounded-xl bg-[#571724] text-white font-bold text-xs hover:bg-[#430F1A] transition">
                        Buka Formulir
                    </button>
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

                    ${bookingCtaHtml}
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

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
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

        // Render user message & hide recommendations
        appendUserMessage(query);

        // Show loading
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
                window.dispatchEvent(new CustomEvent('aegora:assistant-reply', { detail: assistantData }));
            } else {
                throw new Error('Respon tidak valid dari server');
            }
        } catch (error) {
            console.error('Chat send error:', error);
            const dict = i18n[currentLang] || i18n.id;
            const errData = {
                content: dict.error_network,
                rendered_html: `<p>${dict.error_network}</p>`,
                facility: null
            };
            appendAssistantMessage(errData);
            window.dispatchEvent(new CustomEvent('aegora:assistant-reply', { detail: errData }));
        } finally {
            loadingIndicator.classList.add('hidden');
            chatInput.disabled = false;
            sendBtn.disabled = false;
            chatInput.focus();
            isProcessing = false;
            scrollToBottom();
        }
    };

    window.aegoraSendMessage = handleSendMessage;

    // Form submit
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleSendMessage();
    });

    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    });

    // Upload PDF File Handler
    if (attachBtn && pdfFileInput) {
        attachBtn.addEventListener('click', () => {
            pdfFileInput.click();
        });

        pdfFileInput.addEventListener('change', async () => {
            const file = pdfFileInput.files[0];
            if (!file) return;

            if (file.type !== 'application/pdf') {
                alert('Silakan pilih file berformat PDF.');
                pdfFileInput.value = '';
                return;
            }

            // Append User message for uploading
            appendUserMessage(`📄 Mengunggah Dokumen PDF: ${file.name}`);
            loadingIndicator.classList.remove('hidden');
            scrollToBottom();

            const formData = new FormData();
            formData.append('file', file);
            formData.append('session_id', sessionId);
            formData.append('locale', currentLang);

            try {
                const response = await window.axios.post('/api/chat/upload-pdf', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (response.data && response.data.status === 'success') {
                    appendAssistantMessage({
                        content: response.data.data.assistant_reply,
                        rendered_html: response.data.data.rendered_html,
                    });
                }
            } catch (err) {
                console.error('Upload error:', err);
                appendAssistantMessage({
                    content: 'Gagal mengunggah dokumen PDF. Pastikan ukuran file di bawah 10MB.',
                    rendered_html: '<p>Gagal mengunggah dokumen PDF. Pastikan ukuran file di bawah 10MB.</p>',
                });
            } finally {
                loadingIndicator.classList.add('hidden');
                pdfFileInput.value = '';
                scrollToBottom();
            }
        });
    }

    // Modal Booking Handlers
    const openBookingModal = () => {
        if (bookingModal) {
            bookingModal.classList.remove('hidden');
            // Set default dates
            const today = new Date();
            const tomorrow = new Date();
            tomorrow.setDate(today.getDate() + 1);

            const checkinEl = document.getElementById('bkg-checkin');
            const checkoutEl = document.getElementById('bkg-checkout');
            if (checkinEl && !checkinEl.value) checkinEl.value = today.toISOString().split('T')[0];
            if (checkoutEl && !checkoutEl.value) checkoutEl.value = tomorrow.toISOString().split('T')[0];
        }
    };

    const closeBookingModal = () => {
        if (bookingModal) bookingModal.classList.add('hidden');
    };

    if (closeBookingModalBtn) closeBookingModalBtn.addEventListener('click', closeBookingModal);
    if (bookingModal) {
        bookingModal.addEventListener('click', (e) => {
            if (e.target === bookingModal) closeBookingModal();
        });
    }

    // Submit Booking Form
    if (bookingForm) {
        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('bkg-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Mengirim Reservasi...';

            const payload = {
                session_id: sessionId,
                guest_name: document.getElementById('bkg-name').value,
                guest_phone: document.getElementById('bkg-phone').value,
                guest_email: document.getElementById('bkg-email').value,
                room_type: document.getElementById('bkg-room-type').value,
                check_in_date: document.getElementById('bkg-checkin').value,
                check_out_date: document.getElementById('bkg-checkout').value,
                guest_count: parseInt(document.getElementById('bkg-guests').value) || 2,
                special_requests: document.getElementById('bkg-requests').value,
            };

            try {
                const response = await window.axios.post('/api/chat/booking', payload);
                if (response.data && response.data.status === 'success') {
                    closeBookingModal();
                    const d = response.data.data;

                    const voucherHtml = `
                        <div class="mt-2 bg-[#F6F0E0] border border-[#DECFA8] rounded-2xl p-4 space-y-2 shadow-xs">
                            <div class="flex items-center justify-between border-b border-[#E8DEC2] pb-2">
                                <span class="font-bold text-xs text-[#571724]">🎟️ BUKTI RESERVASI KAMAR</span>
                                <span class="font-mono text-xs font-bold text-[#571724] bg-[#EFE3CF] px-2 py-0.5 rounded">${d.booking_code}</span>
                            </div>
                            <div class="text-xs space-y-1 text-stone-700">
                                <p><strong>Nama Tamu:</strong> ${escapeHtml(d.guest_name)}</p>
                                <p><strong>Tipe Kamar:</strong> ${escapeHtml(d.room_type)}</p>
                                <p><strong>Periode:</strong> ${escapeHtml(d.check_in)} – ${escapeHtml(d.check_out)}</p>
                                <p><strong>No. WhatsApp:</strong> ${escapeHtml(d.phone)}</p>
                            </div>
                            <div class="pt-2 text-[11px] text-emerald-800 font-semibold border-t border-[#E8DEC2]">
                                ✓ Data telah diteruskan ke Tim Reservasi Hotel. Kami akan segera menghubungi WhatsApp Anda untuk konfirmasi pembayaran.
                            </div>
                        </div>
                    `;

                    appendAssistantMessage({
                        content: `Voucher Booking Terbit: ${d.booking_code}`,
                        rendered_html: voucherHtml,
                    });
                }
            } catch (err) {
                console.error('Booking submit error:', err);
                alert('Gagal mengirim formulir booking. Mohon periksa kelengkapan data.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim Reservasi ke Pihak Hotel';
            }
        });
    }

    // Click delegation
    document.addEventListener('click', (e) => {
        // Quick prompt click
        const target = e.target.closest('[data-quick-prompt]');
        if (target) {
            const prompt = target.getAttribute('data-quick-prompt');
            if (prompt) handleSendMessage(prompt);
            return;
        }

        // Open booking modal button
        if (e.target.closest('.open-booking-btn')) {
            openBookingModal();
            return;
        }

        // Facility Action Buttons
        const actionBtn = e.target.closest('.facility-action-btn');
        if (actionBtn) {
            const action = actionBtn.getAttribute('data-action');
            if (action === 'menu') {
                handleSendMessage(currentLang === 'en' ? 'Could you provide the menu at The Verandah Restaurant?' : 'Bisa lihat menu di The Verandah Restaurant?');
            } else if (action === 'breakfast') {
                handleSendMessage(currentLang === 'en' ? 'How can I reserve a breakfast table?' : 'Bagaimana cara reservasi meja sarapan?');
            } else if (action === 'reception') {
                openStaffModal();
            }
            return;
        }

        // Feedback thumbs
        const fbBtn = e.target.closest('.feedback-btn');
        if (fbBtn) {
            const dict = i18n[currentLang] || i18n.id;
            const parent = fbBtn.parentElement;
            parent.innerHTML = `<span class="text-[11px] text-emerald-700 font-medium">${dict.feedback_thank_you}</span>`;
            return;
        }
    });

    // Staff modal
    const openStaffModal = () => { if (staffModal) staffModal.classList.remove('hidden'); };
    const closeStaffModal = () => { if (staffModal) staffModal.classList.add('hidden'); };

    if (navStaffBtn) navStaffBtn.addEventListener('click', openStaffModal);
    if (closeStaffModalBtn) closeStaffModalBtn.addEventListener('click', closeStaffModal);
    if (staffModal) {
        staffModal.addEventListener('click', (e) => {
            if (e.target === staffModal) closeStaffModal();
        });
    }

    if (requestStaffChatBtn) {
        requestStaffChatBtn.addEventListener('click', () => {
            closeStaffModal();
            handleSendMessage(currentLang === 'en'
                ? 'Hello, I would like to request hotel staff to call my room.'
                : 'Halo, saya ingin meminta staf resepsionis atau duty manager untuk menghubungi kamar saya.');
        });
    }

    // Load session history if continuing past conversation
    const loadSessionHistory = async (targetSession) => {
        try {
            const res = await window.axios.get(`/api/chat/history?session_id=${encodeURIComponent(targetSession)}`);
            if (res.data && res.data.status === 'success' && res.data.data.messages && res.data.data.messages.length > 0) {
                if (messagesContainer) {
                    messagesContainer.innerHTML = '';
                    res.data.data.messages.forEach((msg) => {
                        if (msg.role === 'user') {
                            appendUserMessage(msg.content, msg.time);
                        } else {
                            appendAssistantMessage({
                                content: msg.content,
                                rendered_html: msg.rendered_html,
                                time: msg.time,
                                facility: msg.facility,
                            });
                        }
                    });
                    hideQuickReplies();
                    scrollToBottom();
                }
            }
        } catch (err) {
            console.error('Gagal memuat riwayat sesi chat:', err);
        }
    };

    if (urlSessionId) {
        loadSessionHistory(urlSessionId);
    }

    // New Chat Button Handler
    if (newChatBtn) {
        newChatBtn.addEventListener('click', () => {
            const dict = i18n[currentLang] || i18n.id;
            if (confirm(dict.confirm_new_chat)) {
                sessionId = 'aegora_' + Math.random().toString(36).substring(2, 11) + '_' + Date.now();
                localStorage.setItem('aegora_session_id', sessionId);
                window.location.href = '/chat';
            }
        });
    }
});
