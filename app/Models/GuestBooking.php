<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestBooking extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'booking_code',
        'session_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'room_type',
        'room_number',
        'room_count',
        'check_in_date',
        'check_out_date',
        'guest_count',
        'price_per_night',
        'nights',
        'subtotal_amount',
        'tax_amount',
        'deposit_amount',
        'total_amount',
        'special_requests',
        'status',
        'payment_status',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'guest_count' => 'integer',
        'room_count' => 'integer',
        'nights' => 'integer',
        'price_per_night' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Build complete folio/billing breakdown including room charges & in-room orders.
     */
    public function getBillingBreakdown(): array
    {
        $nights = $this->nights ?: max(1, (int) $this->check_in_date->diffInDays($this->check_out_date));
        $roomCount = max(1, (int) ($this->room_count ?: 1));
        $pricePerNight = (float) $this->price_per_night;

        if ($pricePerNight <= 0 && $this->room) {
            $pricePerNight = (float) $this->room->price_per_night;
        }
        if ($pricePerNight <= 0) {
            $pricePerNight = str_contains(strtolower($this->room_type), 'suite') ? 2450000 : 1250000;
        }

        $subtotal = (float) ($this->subtotal_amount > 0 ? $this->subtotal_amount : ($pricePerNight * $nights * $roomCount));
        $tax = (float) ($this->tax_amount > 0 ? $this->tax_amount : round($subtotal * 0.21));
        $deposit = (float) ($this->deposit_amount ?? 500000);

        $folioOrdersQuery = RoomOrder::where('billed_to_folio', true);
        if (!empty($this->room_number)) {
            $folioOrdersQuery->where(function ($q) {
                $q->where('room_number', $this->room_number);
                if (!empty($this->session_id)) {
                    $q->orWhere('session_id', $this->session_id);
                }
            });
        } elseif (!empty($this->session_id)) {
            $folioOrdersQuery->where('session_id', $this->session_id);
        } else {
            $folioOrdersQuery->whereRaw('1 = 0');
        }

        $folioOrders = $folioOrdersQuery->get();
        $extraCharges = (float) $folioOrders->sum('total_amount');
        $roomTotal = $subtotal + $tax;
        $grandTotal = $roomTotal + $extraCharges;

        return [
            'nights' => $nights,
            'room_count' => $roomCount,
            'price_per_night' => $pricePerNight,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'extra_charges' => $extraCharges,
            'folio_orders' => $folioOrders,
            'deposit' => $deposit,
            'room_total' => $roomTotal,
            'grand_total' => $grandTotal,
            'payment_status' => $this->payment_status ?: 'unpaid',
        ];
    }
}
