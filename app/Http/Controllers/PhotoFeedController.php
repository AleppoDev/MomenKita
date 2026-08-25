<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoFeedController extends Controller
{
    private const PER_PAGE = 24;

    /** Galeri bergulir tanpa henti; `before` ialah id gambar terakhir yang sudah dipapar. */
    public function index(Request $request): JsonResponse
    {
        $before = $request->integer('before');

        $query = Photo::query()->visible()->latest('id')->limit(self::PER_PAGE + 1);

        if ($before > 0) {
            $query->where('id', '<', $before);
        }

        $photos = $query->get();
        $hasMore = $photos->count() > self::PER_PAGE;
        $photos = $photos->take(self::PER_PAGE);

        return response()->json([
            'photos' => $photos->map(fn (Photo $photo) => $this->present($photo))->all(),
            'has_more' => $hasMore,
            'next_before' => $photos->last()?->id,
        ]);
    }

    /** Gambar baharu sahaja, untuk galeri yang menyegar sendiri semasa majlis. */
    public function since(Request $request): JsonResponse
    {
        $after = $request->integer('after');

        $photos = Photo::query()
            ->visible()
            ->when($after > 0, fn ($query) => $query->where('id', '>', $after))
            ->orderBy('id')
            ->limit(60)
            ->get();

        return response()->json([
            'photos' => $photos->map(fn (Photo $photo) => $this->present($photo))->all(),
            'total' => Photo::query()->visible()->count(),
        ]);
    }

    private function present(Photo $photo): array
    {
        return [
            'id' => $photo->id,
            'name' => $photo->displayName(),
            'caption' => $photo->caption,
            'thumb' => $photo->thumbUrl(),
            'original' => $photo->originalUrl(),
            'width' => $photo->width,
            'height' => $photo->height,
            'ago' => $photo->created_at?->diffForHumans(),
        ];
    }
}
