<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const FIELDS = [
        'bride_name', 'groom_name', 'couple_slug', 'wedding_date',
        'venue_name', 'venue_address', 'hashtag', 'hero_note', 'camera_note',
    ];

    public function edit()
    {
        return view('admin.settings', ['settings' => Setting::map()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bride_name' => ['nullable', 'string', 'max:80'],
            'groom_name' => ['nullable', 'string', 'max:80'],
            'couple_slug' => ['nullable', 'string', 'max:80'],
            'wedding_date' => ['nullable', 'string', 'max:80'],
            'venue_name' => ['nullable', 'string', 'max:120'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'hashtag' => ['nullable', 'string', 'max:60'],
            'hero_note' => ['nullable', 'string', 'max:255'],
            'camera_note' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::putMany(array_intersect_key($validated, array_flip(self::FIELDS)));

        return back()->with('status', 'Tetapan disimpan.');
    }
}
