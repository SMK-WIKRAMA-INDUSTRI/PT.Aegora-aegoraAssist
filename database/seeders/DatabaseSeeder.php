<?php

namespace Database\Seeders;

use App\Models\HotelSetting;
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
        // Staff / Admin Hotel User
        User::firstOrCreate(
            ['email' => 'admin@aegora.com'],
            [
                'name' => 'Duty Manager & Staff Aegora',
                'password' => Hash::make('password123'),
            ]
        );

        // Default Hotel Settings
        HotelSetting::firstOrCreate(['key' => 'hotel_name'], ['value' => 'Aegora Hotel & Hospitality']);
        HotelSetting::firstOrCreate(['key' => 'ai_persona'], ['value' => 'jakarta_business']); // bali_resort, jakarta_business, luxury_heritage
        HotelSetting::firstOrCreate(['key' => 'hotel_phone'], ['value' => '+62 812-3456-7890']);
        HotelSetting::firstOrCreate(['key' => 'proactive_upsell'], ['value' => '1']);
    }
}
