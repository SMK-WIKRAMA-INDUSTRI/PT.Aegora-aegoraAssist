<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\GuestBooking;
use App\Models\HotelKnowledge;
use App\Models\HotelSetting;
use App\Models\HousekeepingTicket;
use App\Models\Room;
use App\Models\RoomOrder;
use App\Models\UrgentEscalation;
use App\Models\User;
use App\Services\DeepgramVoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    /**
     * Ensure only authenticated hotel staff / admin can perform administrative actions.
     */
    protected function ensureStaff(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isStaff()) {
            abort(403, 'Akses khusus Staf & Admin Aegora Hotel.');
        }
    }

    /**
     * Show Hotel Staff & Admin Operations Dashboard.
     */
    public function index(Request $request, DeepgramVoiceService $voiceService)
    {
        $this->ensureStaff();

        $currentPersona = HotelSetting::getVal('ai_persona', 'hospitable_warm');
        $hotelName = HotelSetting::getVal('hotel_name', 'Aegora Hotel & Hospitality');
        $voiceConfig = $voiceService->getPublicConfig();

        $housekeepingTickets = HousekeepingTicket::latest()->take(20)->get();
        $roomOrders = RoomOrder::latest()->take(20)->get();
        $guestBookings = GuestBooking::with('user')->latest()->take(25)->get();
        $escalations = UrgentEscalation::latest()->take(20)->get();

        // Daftar Akun Tamu Terdaftar (dengan pencarian cepat)
        $guestSearch = trim((string) $request->query('guest_search', ''));
        $guestAccountsQuery = User::where('role', 'user')
            ->withCount(['conversations', 'bookings'])
            ->latest();

        if ($guestSearch !== '') {
            $guestAccountsQuery->where(function ($q) use ($guestSearch) {
                $q->where('name', 'like', "%{$guestSearch}%")
                    ->orWhere('email', 'like', "%{$guestSearch}%")
                    ->orWhere('phone', 'like', "%{$guestSearch}%");
            });
        }
        $guestAccounts = $guestAccountsQuery->take(50)->get();

        // Map email & normalized phone yang sudah memiliki akun (untuk tombol "+ Buatkan Akun" di tabel booking)
        $registeredEmails = User::pluck('email')->map(fn ($e) => strtolower((string) $e))->all();

        // PRD Modul 3 (IN-08): Data Kamar & Knowledge Base Hotel
        $rooms = Room::orderBy('price_per_night')->get();
        $knowledgeItems = HotelKnowledge::orderBy('sort_order')->orderBy('id')->get();
        $knowledgeCategories = HotelKnowledge::categories();

        // PRD Bagian 12: Analitik Ringkas
        $stats = [
            'pending_housekeeping' => HousekeepingTicket::where('status', 'pending')->count(),
            'active_orders' => RoomOrder::whereIn('status', ['confirmed', 'preparing'])->count(),
            'pending_bookings' => GuestBooking::where('status', 'pending_hotel_contact')->count(),
            'urgent_alerts' => UrgentEscalation::where('status', 'open')->count(),
            'total_guests' => User::where('role', 'user')->count(),
            'total_conversations' => Conversation::count(),
            'total_rooms' => $rooms->count(),
            'total_knowledge' => $knowledgeItems->count(),
        ];

        return view('admin.dashboard', compact(
            'currentPersona',
            'hotelName',
            'voiceConfig',
            'housekeepingTickets',
            'roomOrders',
            'guestBookings',
            'escalations',
            'guestAccounts',
            'guestSearch',
            'registeredEmails',
            'rooms',
            'knowledgeItems',
            'knowledgeCategories',
            'stats'
        ));
    }

    /**
     * Update AI Persona Style.
     */
    public function updatePersona(Request $request)
    {
        $this->ensureStaff();

        $validated = $request->validate([
            'persona' => 'required|string|in:hospitable_warm,bali_resort,jakarta_business,luxury_heritage',
        ]);

        HotelSetting::setVal('ai_persona', $validated['persona']);

        return back()->with('success', 'Gaya bahasa & persona AI berhasil diperbarui!');
    }

    /**
     * Update Deepgram Voice Character Settings (TTS Indonesia & English).
     */
    public function updateVoiceSettings(Request $request)
    {
        $this->ensureStaff();

        $validIdVoices = array_keys(config('deepgram.voices.id', []));
        $validEnVoices = array_keys(config('deepgram.voices.en', []));

        $validated = $request->validate([
            'deepgram_voice_id' => ['required', 'string', 'in:' . implode(',', $validIdVoices)],
            'deepgram_voice_en' => ['required', 'string', 'in:' . implode(',', $validEnVoices)],
        ]);

        HotelSetting::setVal('deepgram_voice_id', $validated['deepgram_voice_id']);
        HotelSetting::setVal('deepgram_voice_en', $validated['deepgram_voice_en']);

        return back()->with('success', 'Karakter suara AI Deepgram (Indonesia & Inggris) berhasil disimpan!');
    }

    /**
     * Update Housekeeping Ticket Status.
     */
    public function updateTicketStatus(Request $request, HousekeepingTicket $ticket)
    {
        $this->ensureStaff();

        $ticket->update(['status' => $request->input('status', 'completed')]);
        return back()->with('success', 'Status tiket housekeeping berhasil diperbarui.');
    }

    /**
     * Update Urgent Escalation Status.
     */
    public function updateEscalationStatus(Request $request, UrgentEscalation $escalation)
    {
        $this->ensureStaff();

        $escalation->update(['status' => $request->input('status', 'resolved')]);
        return back()->with('success', 'Status eskalasi komplain berhasil ditangani.');
    }

    /**
     * Update Booking Status, Room Number & Payment Status (PRD Modul 2 & 4).
     */
    public function updateBookingStatus(Request $request, GuestBooking $booking)
    {
        $this->ensureStaff();

        $validated = $request->validate([
            'status' => 'required|string|in:pending_hotel_contact,confirmed,checked_in,checked_out,cancelled',
            'payment_status' => 'nullable|string|in:unpaid,dp_paid,paid,refunded',
            'room_number' => 'nullable|string|max:20',
        ]);

        $updates = ['status' => $validated['status']];
        if (!empty($validated['payment_status'])) {
            $updates['payment_status'] = $validated['payment_status'];
        }
        if ($request->has('room_number')) {
            $updates['room_number'] = $validated['room_number'];
        }
        if ($validated['status'] === 'checked_in' && !$booking->checked_in_at) {
            $updates['checked_in_at'] = now();
        }
        if ($validated['status'] === 'checked_out' && !$booking->checked_out_at) {
            $updates['checked_out_at'] = now();
        }

        $booking->update($updates);

        return back()->with('success', "Status reservasi {$booking->booking_code} berhasil diperbarui.");
    }

    /**
     * Fitur Registrasi Akun Tamu oleh Admin (jika tamu belum memiliki akun).
     */
    public function storeGuest(Request $request)
    {
        $this->ensureStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6|max:64',
        ], [
            'email.unique' => 'Alamat email tamu tersebut sudah terdaftar di sistem.',
            'password.min' => 'Kata sandi sementara minimal 6 karakter.',
        ]);

        $plainPassword = $validated['password'];

        $guest = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($plainPassword),
            'role' => 'user',
        ]);

        // Otomatis hubungkan booking yang sebelumnya dibuat tamu ini (berdasarkan email atau nomor HP)
        $cleanPhoneTail = substr(preg_replace('/[^0-9]/', '', $guest->phone), -8);
        GuestBooking::whereNull('user_id')
            ->where(function ($q) use ($guest, $cleanPhoneTail) {
                $q->where('guest_email', $guest->email);
                if (strlen($cleanPhoneTail) >= 7) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(guest_phone, '-', ''), ' ', ''), '+', '') LIKE ?", ['%' . $cleanPhoneTail]);
                }
            })
            ->update(['user_id' => $guest->id]);

        return back()
            ->with('success', "Akun tamu atas nama {$guest->name} berhasil didaftarkan oleh Admin!")
            ->with('created_guest_credentials', [
                'name' => $guest->name,
                'email' => $guest->email,
                'phone' => $guest->phone,
                'password' => $plainPassword,
            ]);
    }

    /**
     * Reset Guest Account Password by Admin.
     */
    public function resetGuestPassword(Request $request, User $user)
    {
        $this->ensureStaff();

        if ($user->isStaff()) {
            return back()->withErrors(['guest' => 'Tidak dapat mereset akun staf dari panel registrasi tamu.']);
        }

        $validated = $request->validate([
            'new_password' => 'required|string|min:6|max:64',
        ]);

        $user->update(['password' => Hash::make($validated['new_password'])]);

        return back()
            ->with('success', "Password akun tamu {$user->name} berhasil direset.")
            ->with('created_guest_credentials', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'password' => $validated['new_password'],
            ]);
    }

    /**
     * Delete a Guest Account by Admin.
     */
    public function destroyGuest(User $user)
    {
        $this->ensureStaff();

        if ($user->isStaff()) {
            return back()->withErrors(['guest' => 'Akun staf tidak dapat dihapus dari daftar tamu.']);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Akun tamu {$name} telah dihapus.");
    }

    /**
     * PRD Modul 3 (IN-08): Tambah Tipe Kamar Resmi.
     */
    public function storeRoom(Request $request)
    {
        $this->ensureStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|string|in:deluxe,suite,family,standard',
            'price_per_night' => 'required|numeric|min:100000',
            'tax_percent' => 'nullable|numeric|min:0|max:50',
            'capacity' => 'required|integer|min:1|max:12',
            'size_sqm' => 'required|integer|min:12|max:500',
            'bed_type' => 'required|string|max:100',
            'total_units' => 'required|integer|min:1|max:200',
            'facilities_text' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|string|max:500',
        ]);

        $facilities = array_values(array_filter(array_map('trim', explode(',', (string) ($validated['facilities_text'] ?? '')))));

        Room::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
            'category' => $validated['category'],
            'price_per_night' => $validated['price_per_night'],
            'tax_percent' => $validated['tax_percent'] ?? 21,
            'capacity' => $validated['capacity'],
            'size_sqm' => $validated['size_sqm'],
            'bed_type' => $validated['bed_type'],
            'total_units' => $validated['total_units'],
            'facilities' => $facilities,
            'description' => $validated['description'] ?? null,
            'image_url' => !empty($validated['image_url'])
                ? $validated['image_url']
                : 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=800&q=80',
            'is_available' => true,
        ]);

        return back()->with('success', "Tipe kamar '{$validated['name']}' berhasil ditambahkan ke database hotel!");
    }

    /**
     * PRD Modul 3 (IN-08): Perbarui Tipe Kamar Resmi.
     */
    public function updateRoom(Request $request, Room $room)
    {
        $this->ensureStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'price_per_night' => 'required|numeric|min:100000',
            'capacity' => 'required|integer|min:1|max:12',
            'total_units' => 'required|integer|min:1|max:200',
            'is_available' => 'nullable|boolean',
        ]);

        $room->update([
            'name' => $validated['name'],
            'price_per_night' => $validated['price_per_night'],
            'capacity' => $validated['capacity'],
            'total_units' => $validated['total_units'],
            'is_available' => $request->boolean('is_available', true),
        ]);

        return back()->with('success', "Data kamar '{$room->name}' berhasil diperbarui.");
    }

    /**
     * PRD Modul 3 (IN-08): Hapus Tipe Kamar.
     */
    public function destroyRoom(Room $room)
    {
        $this->ensureStaff();
        $name = $room->name;
        $room->delete();

        return back()->with('success', "Tipe kamar '{$name}' berhasil dihapus.");
    }

    /**
     * PRD Modul 3 (IN-08): Tambah Item Knowledge Base Hotel (Fasilitas, Jam, Kebijakan, FAQ).
     */
    public function storeKnowledge(Request $request)
    {
        $this->ensureStaff();

        $validated = $request->validate([
            'category' => 'required|string|in:profile,facility,restaurant,hours,policy,transport,contact,faq',
            'title' => 'required|string|max:150',
            'location' => 'nullable|string|max:150',
            'operating_hours' => 'nullable|string|max:150',
            'extra_fee' => 'nullable|string|max:150',
            'content' => 'required|string|max:2000',
            'content_en' => 'nullable|string|max:2000',
        ]);

        HotelKnowledge::create($validated + ['is_active' => true]);

        return back()->with('success', "Informasi hotel '{$validated['title']}' berhasil ditambahkan ke Knowledge Base AI!");
    }

    /**
     * PRD Modul 3 (IN-08): Perbarui Item Knowledge Base Hotel.
     */
    public function updateKnowledge(Request $request, HotelKnowledge $knowledge)
    {
        $this->ensureStaff();

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'location' => 'nullable|string|max:150',
            'operating_hours' => 'nullable|string|max:150',
            'content' => 'required|string|max:2000',
            'content_en' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        $knowledge->update([
            'title' => $validated['title'],
            'location' => $validated['location'] ?? null,
            'operating_hours' => $validated['operating_hours'] ?? null,
            'content' => $validated['content'],
            'content_en' => $validated['content_en'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Knowledge Base '{$knowledge->title}' berhasil diperbarui!");
    }

    /**
     * PRD Modul 3 (IN-08): Hapus Item Knowledge Base Hotel.
     */
    public function destroyKnowledge(HotelKnowledge $knowledge)
    {
        $this->ensureStaff();
        $title = $knowledge->title;
        $knowledge->delete();

        return back()->with('success', "Informasi '{$title}' telah dihapus dari Knowledge Base.");
    }
}
