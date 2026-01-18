<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        $socialLinks = [
            'facebook' => Setting::getValue('social_facebook', ''),
            'twitter' => Setting::getValue('social_twitter', ''),
            'instagram' => Setting::getValue('social_instagram', ''),
            'youtube' => Setting::getValue('social_youtube', ''),
            'linkedin' => Setting::getValue('social_linkedin', ''),
            'tiktok' => Setting::getValue('social_tiktok', ''),
            'whatsapp' => Setting::getValue('social_whatsapp', ''),
            'telegram' => Setting::getValue('social_telegram', ''),
        ];
        
        return view('maintenance', compact('socialLinks'));
    }
}
