<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $photos = Photo::query()
            ->latest('id')
            ->paginate(48)
            ->withQueryString();

        return view('admin.dashboard', [
            'photos' => $photos,
            'totalPhotos' => Photo::query()->count(),
            'hiddenPhotos' => Photo::query()->where('is_hidden', true)->count(),
            'totalBytes' => (int) Photo::query()->sum('bytes'),
            'contributors' => Photo::query()->whereNotNull('guest_name')->distinct()->count('guest_name'),
            'zipAvailable' => class_exists(\ZipArchive::class),
        ]);
    }
}
