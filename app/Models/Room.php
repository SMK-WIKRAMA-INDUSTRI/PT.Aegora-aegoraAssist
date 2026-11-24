<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'price_per_night',
        'tax_percent',
        'capacity',
        'size_sqm',
        'bed_type',
        'total_units',
        'facilities',
        'image_url',
        'is_available',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'capacity' => 'integer',
        'size_sqm' => 'integer',
        'total_units' => 'integer',
        'facilities' => 'array',
        'is_available' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(GuestBooking::class);
    }

    /**
     * Calculate available room units for a date range.
     */
    public function availableUnitsForDates(string $checkIn, string $checkOut): int
    {
        if (!$this->is_available) {
            return 0;
        }

        $booked = $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->sum('room_count');

        return max(0, $this->total_units - (int) $booked);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'IDR ' . number_format((float) $this->price_per_night, 0, ',', '.');
    }
}
