<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Setting;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing', [
            'settings' => Setting::map(),
            'photoCount' => Photo::query()->visible()->count(),
            'photos' => Photo::query()->visible()->latest()->limit(24)->get(),
        ]);
    }
}
