<?php

namespace App\Http\Controllers;

use App\Models\GuestBooking;
use App\Models\HotelSetting;
use App\Models\HousekeepingTicket;
use App\Models\RoomOrder;
use App\Models\UrgentEscalation;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Show Hotel Staff & Admin Operations Dashboard.
     */
    public function index()
    {
        $currentPersona = HotelSetting::getVal('ai_persona', 'jakarta_business');
        $hotelName = HotelSetting::getVal('hotel_name', 'Aegora Hotel & Hospitality');

        $housekeepingTickets = HousekeepingTicket::latest()->take(20)->get();
        $roomOrders = RoomOrder::latest()->take(20)->get();
        $guestBookings = GuestBooking::latest()->take(20)->get();
        $escalations = UrgentEscalation::latest()->take(20)->get();

        $stats = [
            'pending_housekeeping' => HousekeepingTicket::where('status', 'pending')->count(),
            'active_orders' => RoomOrder::whereIn('status', ['confirmed', 'preparing'])->count(),
            'pending_bookings' => GuestBooking::where('status', 'pending_hotel_contact')->count(),
            'urgent_alerts' => UrgentEscalation::where('status', 'open')->count(),
        ];

        return view('admin.dashboard', compact(
            'currentPersona',
            'hotelName',
            'housekeepingTickets',
            'roomOrders',
            'guestBookings',
            'escalations',
            'stats'
        ));
    }

    /**
     * Update AI Persona Style.
     */
    public function updatePersona(Request $request)
    {
        $validated = $request->validate([
            'persona' => 'required|string|in:bali_resort,jakarta_business,luxury_heritage',
        ]);

        HotelSetting::setVal('ai_persona', $validated['persona']);

        return back()->with('success', 'Gaya bahasa & persona AI berhasil diperbarui!');
    }

    /**
     * Update Housekeeping Ticket Status.
     */
    public function updateTicketStatus(Request $request, HousekeepingTicket $ticket)
    {
        $ticket->update(['status' => $request->input('status', 'completed')]);
        return back()->with('success', 'Status tiket housekeeping berhasil diperbarui.');
    }

    /**
     * Update Urgent Escalation Status.
     */
    public function updateEscalationStatus(Request $request, UrgentEscalation $escalation)
    {
        $escalation->update(['status' => $request->input('status', 'resolved')]);
        return back()->with('success', 'Status eskalasi komplain berhasil ditangani.');
    }
}
