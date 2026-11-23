<?php

namespace App\Http\Controllers;

use App\Models\HotelSetting;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Show the luxury hotel landing page.
     */
    public function index()
    {
        $hotelName = HotelSetting::getVal('hotel_name', 'Aegora Hotel & Luxury Resort');
        $persona = HotelSetting::getVal('ai_persona', 'jakarta_business');

        return view('landing', compact('hotelName', 'persona'));
    }
}
