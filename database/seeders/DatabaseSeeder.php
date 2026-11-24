<?php

namespace Database\Seeders;

use App\Models\GuestBooking;
use App\Models\HotelKnowledge;
use App\Models\HotelSetting;
use App\Models\Room;
use App\Models\RoomOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Staff / Admin Hotel User
        User::updateOrCreate(
            ['email' => 'admin@aegora.com'],
            [
                'name' => 'Duty Manager & Staff Aegora',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'phone' => '+62 811-9876-5432',
            ]
        );

        // 2. Guest / User Demo Account
        $demoGuest = User::updateOrCreate(
            ['email' => 'tamu@aegora.com'],
            [
                'name' => 'Bpk. Hendra Wijaya',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '+62 812-3456-7890',
            ]
        );

        // 3. Default Hotel Settings (termasuk konfigurasi suara Deepgram & persona hangat)
        HotelSetting::firstOrCreate(['key' => 'hotel_name'], ['value' => 'Aegora Hotel & Hospitality']);
        HotelSetting::firstOrCreate(['key' => 'ai_persona'], ['value' => 'hospitable_warm']);
        HotelSetting::firstOrCreate(['key' => 'hotel_phone'], ['value' => '+62 812-3456-7890']);
        HotelSetting::firstOrCreate(['key' => 'proactive_upsell'], ['value' => '1']);
        HotelSetting::firstOrCreate(['key' => 'deepgram_voice_id'], ['value' => 'flux-fitri-id']);
        HotelSetting::firstOrCreate(['key' => 'deepgram_voice_en'], ['value' => 'aura-2-helena-en']);

        // 4. Data Kamar Resmi Aegora Hotel (PRD Modul 2 & 3)
        $rooms = [
            [
                'name' => 'Deluxe King Room',
                'slug' => 'deluxe-king-room',
                'category' => 'deluxe',
                'description' => 'Kamar modern seluas 36 m² dengan pemandangan kota, tempat tidur King Koil plush, walk-in rain shower, Smart TV 55", serta sarapan gratis untuk 2 orang.',
                'price_per_night' => 1250000,
                'tax_percent' => 21,
                'capacity' => 2,
                'size_sqm' => 36,
                'bed_type' => '1 King Bed (King Koil)',
                'total_units' => 12,
                'facilities' => ['AC', 'Wi-Fi 100 Mbps', 'Sarapan 2 Orang', 'Smart TV 55"', 'Walk-in Rain Shower', 'Mini Bar', 'Coffee & Tea Maker'],
                'image_url' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=800&q=80',
                'is_available' => true,
            ],
            [
                'name' => 'Deluxe Twin Room',
                'slug' => 'deluxe-twin-room',
                'category' => 'deluxe',
                'description' => 'Kamar nyaman 36 m² dengan dua tempat tidur single terpisah, cocok untuk perjalanan bisnis maupun sahabat, termasuk akses kolam renang & sarapan untuk 2 orang.',
                'price_per_night' => 1250000,
                'tax_percent' => 21,
                'capacity' => 2,
                'size_sqm' => 36,
                'bed_type' => '2 Twin Beds',
                'total_units' => 10,
                'facilities' => ['AC', 'Wi-Fi 100 Mbps', 'Sarapan 2 Orang', 'Smart TV 55"', 'Meja Kerja', 'Walk-in Rain Shower', 'Safe Deposit Box'],
                'image_url' => 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?auto=format&fit=crop&w=800&q=80',
                'is_available' => true,
            ],
            [
                'name' => 'Executive Club Suite',
                'slug' => 'executive-club-suite',
                'category' => 'suite',
                'description' => 'Suite mewah 58 m² dengan ruang tamu terpisah, bathtub marmer, mesin kopi Nespresso, serta akses gratis Executive Club Lounge Lantai 21 (Evening Cocktails & Canapés).',
                'price_per_night' => 2450000,
                'tax_percent' => 21,
                'capacity' => 3,
                'size_sqm' => 58,
                'bed_type' => '1 Super King Bed + Sofa Lounge',
                'total_units' => 6,
                'facilities' => ['AC', 'Wi-Fi 100 Mbps', 'Sarapan 2 Orang', 'Akses Club Lounge Lt. 21', 'Bathtub Marmer', 'Mesin Kopi Nespresso', 'Ruang Tamu Terpisah'],
                'image_url' => 'https://images.unsplash.com/photo-1591088398332-8a7791972843?auto=format&fit=crop&w=800&q=80',
                'is_available' => true,
            ],
            [
                'name' => 'Family Panorama Suite',
                'slug' => 'family-panorama-suite',
                'category' => 'family',
                'description' => 'Suite keluarga luas 74 m² dengan 2 area tidur, bathtub hangat, balkon panorama kota, dan sarapan untuk 4 orang. Ideal untuk liburan keluarga.',
                'price_per_night' => 3150000,
                'tax_percent' => 21,
                'capacity' => 4,
                'size_sqm' => 74,
                'bed_type' => '1 King Bed + 2 Single Beds',
                'total_units' => 4,
                'facilities' => ['AC', 'Wi-Fi 100 Mbps', 'Sarapan 4 Orang', 'Bathtub', 'Balkon Panorama', 'Kids Welcome Kit', 'Smart TV 65"'],
                'image_url' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=800&q=80',
                'is_available' => true,
            ],
        ];

        $deluxeKing = null;
        foreach ($rooms as $roomData) {
            $room = Room::updateOrCreate(['slug' => $roomData['slug']], $roomData);
            if ($room->slug === 'deluxe-king-room') {
                $deluxeKing = $room;
            }
        }

        // 5. Knowledge Base Resmi Aegora Hotel (PRD Modul 3: IN-01 s/d IN-08)
        $knowledgeEntries = [
            [
                'category' => 'profile',
                'title' => 'Profil & Alamat Aegora Hotel',
                'location' => 'Jl. Jenderal Sudirman Kav. 88, Jakarta Pusat 10220',
                'operating_hours' => 'Resepsionis & Concierge 24 Jam',
                'extra_fee' => null,
                'content' => 'Aegora Hotel (PT Aegora) adalah hotel bintang lima bergaya hangat dan modern di jantung Jakarta Pusat, hanya 10 menit dari kawasan bisnis SCBD dan 40 menit dari Bandara Soekarno-Hatta.',
                'content_en' => 'Aegora Hotel (PT Aegora) is a warm, modern 5-star hotel in the heart of Central Jakarta, 10 minutes from SCBD and 40 minutes from Soekarno-Hatta International Airport.',
                'sort_order' => 1,
            ],
            [
                'category' => 'facility',
                'title' => 'Outdoor Infinity Pool & Bar',
                'location' => 'Lantai 3 Deck Selatan',
                'operating_hours' => 'Setiap hari, 07.00 – 20.00 WIB (Pool Bar s/d 22.00 WIB)',
                'extra_fee' => 'Gratis untuk tamu menginap (termasuk handuk bersih)',
                'content' => 'Kolam renang outdoor infinity dengan pemandangan kota, dilengkapi Kids Splash Zone, handuk segar gratis, serta Poolside Mocktail Bar.',
                'content_en' => 'Outdoor infinity pool on the 3rd floor South Deck with city skyline views, complimentary fresh towels, a Kids Splash Zone, and Poolside Mocktail Bar.',
                'image_url' => '/images/infinity-pool.jpg',
                'sort_order' => 2,
            ],
            [
                'category' => 'restaurant',
                'title' => 'The Verandah Restaurant',
                'location' => 'Lantai 2 (Sayap Timur)',
                'operating_hours' => 'Sarapan 06.30–10.30 WIB | Makan Siang 12.00–15.00 WIB | Makan Malam 18.00–22.30 WIB',
                'extra_fee' => 'Sarapan termasuk paket kamar (tamu tambahan Rp 185.000++/orang)',
                'content' => 'Restoran utama Aegora Hotel menyajikan sarapan prasmanan Nusantara & artisanal internasional setiap pagi pukul 06.30–10.30 WIB, serta layanan In-Room Dining 24 jam.',
                'content_en' => 'Our signature restaurant on Level 2 serves daily buffet breakfast from 06:30 to 10:30 WIB, lunch 12:00–15:00, dinner 18:00–22:30, plus 24-hour In-Room Dining.',
                'sort_order' => 3,
            ],
            [
                'category' => 'facility',
                'title' => 'Aegora Spa & Wellness',
                'location' => 'Lantai 5',
                'operating_hours' => 'Setiap hari, 09.00 – 22.00 WIB',
                'extra_fee' => 'Mulai Rp 480.000++ (Aromatherapy Massage 60 menit)',
                'content' => 'Layanan pijat aromaterapi tradisional Bali & Jawa, refleksi, sauna, dan perawatan relaksasi pribadi oleh terapis profesional. Reservasi bisa langsung melalui chat ini.',
                'content_en' => 'Traditional Indonesian aromatherapy massage, reflexology, sauna, and private wellness treatments on Level 5 starting from IDR 480,000++.',
                'sort_order' => 4,
            ],
            [
                'category' => 'facility',
                'title' => 'Fitness Center (Gym)',
                'location' => 'Lantai 5 (Samping Aegora Spa)',
                'operating_hours' => '24 Jam (akses dengan kartu kunci kamar)',
                'extra_fee' => 'Gratis untuk tamu menginap',
                'content' => 'Pusat kebugaran lengkap dengan alat kardio Technogym, free weights, ruang yoga, air mineral, dan handuk dingin gratis.',
                'content_en' => '24-hour fitness center on Level 5 equipped with Technogym cardio machines, free weights, yoga mats, and complimentary chilled towels.',
                'sort_order' => 5,
            ],
            [
                'category' => 'facility',
                'title' => 'Meeting Room, Parkir & Wi-Fi Hotel',
                'location' => 'Meeting Room Lt. 4 | Parkir Basement B1–B3',
                'operating_hours' => 'Wi-Fi & Parkir 24 Jam | Meeting Room 07.00–22.00 WIB',
                'extra_fee' => 'Wi-Fi & Parkir Valet Gratis untuk tamu menginap',
                'content' => 'Wi-Fi berkecepatan tinggi 100 Mbps tersedia di seluruh area (Jaringan: Aegora_Guest_5G, login menggunakan nomor kamar & nama belakang). Tersedia 4 ruang meeting kapasitas 15–120 orang serta parkir basement gratis.',
                'content_en' => 'Complimentary 100 Mbps Wi-Fi throughout the hotel (SSID: Aegora_Guest_5G, log in with room number & last name). Four meeting rooms on Level 4 and free basement/valet parking.',
                'sort_order' => 6,
            ],
            [
                'category' => 'hours',
                'title' => 'Jam Check-in, Check-out, Early Check-in & Late Check-out',
                'location' => 'Lobi Utama Lantai 1 (Front Desk)',
                'operating_hours' => 'Check-in mulai 14.00 WIB | Check-out maksimal 12.00 WIB',
                'extra_fee' => 'Early check-in (<12.00) / Late check-out s/d 16.00: 50% tarif malam (tergantung ketersediaan)',
                'content' => 'Waktu check-in standar mulai pukul 14.00 WIB dan check-out paling lambat pukul 12.00 WIB. Saat check-in, mohon siapkan KTP/Paspor asli dan deposit keamanan Rp 500.000/kamar (tunai atau pra-otorisasi kartu, dikembalikan penuh saat check-out). Late check-out hingga 13.00 WIB dapat dibantu gratis bila kamar tersedia; hingga 16.00 WIB dikenakan biaya 50% tarif 1 malam.',
                'content_en' => 'Standard check-in starts at 14:00 WIB and check-out is by 12:00 WIB. Please present a valid ID/Passport and a refundable security deposit of IDR 500,000 per room. Late check-out until 16:00 is 50% of one night rate subject to availability.',
                'sort_order' => 7,
            ],
            [
                'category' => 'policy',
                'title' => 'Kebijakan Pembatalan, Anak-anak, Hewan Peliharaan & Merokok',
                'location' => 'Seluruh Area Hotel',
                'operating_hours' => 'Berlaku setiap saat',
                'extra_fee' => 'Extra bed Rp 350.000++/malam (termasuk sarapan 1 orang)',
                'content' => '1) Pembatalan gratis hingga 24 jam sebelum tanggal check-in (H-1 pukul 14.00 WIB); pembatalan kurang dari 24 jam atau no-show dikenakan biaya 1 malam. 2) Anak di bawah 6 tahun menginap & sarapan gratis bersama orang tua (tanpa extra bed); usia 6–12 tahun biaya sarapan 50%. 3) Hewan peliharaan belum diperkenankan demi kenyamanan bersama (kecuali hewan pemandu bersertifikat). 4) Seluruh kamar adalah area bebas rokok; tersedia Smoking Lounge di Taman Lantai 3 (denda pembersihan Rp 1.500.000 bila merokok di dalam kamar).',
                'content_en' => '1) Free cancellation up to 24 hours before check-in; late cancellations or no-shows incur 1 night charge. 2) Children under 6 stay and dine free with parents using existing bedding. 3) Pets are not permitted (except certified service animals). 4) All rooms are 100% non-smoking; a dedicated smoking terrace is available on Level 3.',
                'sort_order' => 8,
            ],
            [
                'category' => 'transport',
                'title' => 'Airport Shuttle & Akses Transportasi',
                'location' => 'Lobby Drop-off Utama',
                'operating_hours' => 'Jadwal Shuttle Bandara: 06.00, 10.00, 14.00, 18.00, 21.00 WIB',
                'extra_fee' => 'Rp 250.000/sekali jalan (Gratis untuk tamu Executive & Family Suite)',
                'content' => 'Tersedia layanan antar-jemput Bandara Soekarno-Hatta (estimasi 40–50 menit) dan stasiun Gambir/MRT Sudirman (5 menit jalan kaki). Mohon konfirmasi jadwal shuttle minimal 3 jam sebelumnya.',
                'content_en' => 'Airport shuttle to Soekarno-Hatta Airport (40–50 mins) departs at 06:00, 10:00, 14:00, 18:00, and 21:00 WIB (IDR 250,000/way, complimentary for Suite guests). 5 minutes walk to MRT Sudirman.',
                'sort_order' => 9,
            ],
            [
                'category' => 'contact',
                'title' => 'Kontak Resmi & Nomor Darurat Hotel',
                'location' => 'Front Desk Lantai 1',
                'operating_hours' => '24 Jam / 7 Hari',
                'extra_fee' => null,
                'content' => 'Front Desk (Intercom Kamar): Tekan 0 | WhatsApp Concierge: +62 812-3456-7890 | Telepon Hotel: (021) 5099-8800 | Email: cs@aegora.com | Darurat Medis/Keamanan/Kebakaran: Tekan 9 atau 0 segera (Tim Keamanan & Klinik 24 jam siaga).',
                'content_en' => 'Front Desk (In-Room Phone): Dial 0 | WhatsApp Concierge: +62 812-3456-7890 | Hotel Line: +62 21 5099 8800 | Email: cs@aegora.com | Emergency (Medical/Security/Fire): Dial 9 or 0 immediately.',
                'sort_order' => 10,
            ],
            [
                'category' => 'faq',
                'title' => 'Prosedur Check-out & Pembayaran Tagihan',
                'location' => 'Front Desk Lantai 1 / Express Drop Box',
                'operating_hours' => 'Check-out maksimal 12.00 WIB',
                'extra_fee' => null,
                'content' => 'Untuk check-out, silakan cek rincian tagihan melalui Aegora Assist atau Front Desk, serahkan kartu kunci kamar ke resepsionis, tim kami melakukan pemeriksaan singkat kamar (3–5 menit), pelunasan sisa tagihan, dan pengembalian deposit keamanan secara penuh. Kami menerima tunai, QRIS, debit, serta kartu kredit di Front Desk (tidak memproses kartu kredit di dalam chat).',
                'content_en' => 'For check-out, review your folio via Aegora Assist or the Front Desk, return your key cards at reception, wait 3–5 minutes for a quick room check, settle any remaining balance, and receive your full security deposit refund.',
                'sort_order' => 11,
            ],
        ];

        foreach ($knowledgeEntries as $entry) {
            HotelKnowledge::updateOrCreate(
                ['title' => $entry['title']],
                $entry
            );
        }

        // 6. Demo Booking + Room Folio untuk Uji Modul 4 (Cari Reservasi, Verifikasi Identitas & Cek Tagihan)
        $demoBooking = GuestBooking::updateOrCreate(
            ['booking_code' => 'AEG-BKG-884210'],
            [
                'user_id' => $demoGuest->id,
                'room_id' => $deluxeKing?->id,
                'session_id' => 'demo_hendra_session',
                'guest_name' => 'Bpk. Hendra Wijaya',
                'guest_phone' => '+62 812-3456-7890',
                'guest_email' => 'tamu@aegora.com',
                'room_type' => 'Deluxe King Room',
                'room_number' => '304',
                'room_count' => 1,
                'check_in_date' => now()->format('Y-m-d'),
                'check_out_date' => now()->addDays(2)->format('Y-m-d'),
                'guest_count' => 2,
                'price_per_night' => 1250000,
                'nights' => 2,
                'subtotal_amount' => 2500000,
                'tax_amount' => 525000,
                'deposit_amount' => 500000,
                'total_amount' => 3025000,
                'special_requests' => 'Non-smoking room, lantai atas',
                'status' => 'confirmed',
                'payment_status' => 'dp_paid',
                'checked_in_at' => now()->setTime(14, 15),
            ]
        );

        // Demo Room Order untuk folio kamar 304
        RoomOrder::firstOrCreate(
            ['order_code' => 'FNB-30401'],
            [
                'session_id' => $demoBooking->session_id,
                'room_number' => '304',
                'type' => 'fnb',
                'items_json' => ['request_text' => '1x Nasi Goreng Aegora & 2x Teh Hangat'],
                'total_amount' => 145000,
                'billed_to_folio' => true,
                'status' => 'delivered',
            ]
        );
    }
}
