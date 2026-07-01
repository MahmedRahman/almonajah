<?php

namespace App\Http\Controllers;

use App\Support\SocialLinks;

class MaintenanceController extends Controller
{
    public function index()
    {
        $socialLinks = SocialLinks::all();
        
        return view('maintenance', compact('socialLinks'));
    }
}
