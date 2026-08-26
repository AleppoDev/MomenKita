<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private const FIELDS = [
        'bride_name', 'groom_name', 'groom_father', 'groom_mother',
        'couple_slug', 'wedding_date',
        'venue_name', 'venue_address', 'hashtag', 'hero_note', 'camera_note',
    ];

    /** Had gambar pengantin dalam kilobait. */
    private const PHOTO_MAX_KILOBYTES = 8192;

    public function edit()
    {
        return view('admin.settings', ['settings' => Setting::map()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bride_name' => ['nullable', 'string', 'max:80'],
            'groom_name' => ['nullable', 'string', 'max:80'],
            'groom_father' => ['nullable', 'string', 'max:80'],
            'groom_mother' => ['nullable', 'string', 'max:80'],
            'couple_slug' => ['nullable', 'string', 'max:80'],
            'wedding_date' => ['nullable', 'string', 'max:80'],
            'venue_name' => ['nullable', 'string', 'max:120'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'hashtag' => ['nullable', 'string', 'max:60'],
            'hero_note' => ['nullable', 'string', 'max:255'],
            'camera_note' => ['nullable', 'string', 'max:255'],
            'couple_photo' => ['nullable', 'file', 'image', 'max:' . self::PHOTO_MAX_KILOBYTES],
        ], [
            'couple_photo.image' => 'Fail itu bukan gambar.',
            'couple_photo.max' => 'Gambar terlalu besar. Had ialah 8 MB.',
        ]);

        Setting::putMany(array_intersect_key($validated, array_flip(self::FIELDS)));

        if ($request->hasFile('couple_photo')) {
            $this->replacePhoto($request->file('couple_photo'));
        }

        return back()->with('status', 'Tetapan disimpan.');
    }

    /** Simpan gambar baharu dan buang yang lama supaya storan tidak bertimbun. */
    private function replacePhoto(UploadedFile $file): void
    {
        $disk = Storage::disk('public');
        $previous = Setting::get('couple_photo');

        $path = $disk->putFile('majlis', $file);
        Setting::put('couple_photo', $path);

        if ($previous && $previous !== $path && $disk->exists($previous)) {
            $disk->delete($previous);
        }
    }
}
