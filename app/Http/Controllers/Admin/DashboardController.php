<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archive;
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
            'cameraWillWork' => $this->cameraWillWork($request),
            'archives' => Archive::query()->latest('id')->limit(12)->get(),
            'batchSize' => Archive::BATCH_SIZE,
        ]);
    }

    /**
     * getUserMedia hanya wujud dalam konteks selamat: https, atau localhost.
     * Tanpa semakan ini kegagalan itu senyap — laman kelihatan sempurna dan
     * butang kamera hanya tidak berbuat apa-apa untuk setiap tetamu.
     */
    private function cameraWillWork(Request $request): bool
    {
        return $request->isSecure()
            || in_array($request->getHost(), ['localhost', '127.0.0.1'], true);
    }
}
