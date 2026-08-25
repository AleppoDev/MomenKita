<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Services\PhotoStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PhotoUploadController extends Controller
{
    /** Had setiap gambar dalam kilobait; telefon moden jarang melebihi ini. */
    private const MAX_KILOBYTES = 25600;

    public function __construct(private readonly PhotoStorage $storage)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:' . self::MAX_KILOBYTES],
            'guest_name' => ['nullable', 'string', 'max:80'],
            'caption' => ['nullable', 'string', 'max:500'],
            'upload_token' => ['nullable', 'string', 'max:64'],
        ], [
            'photo.required' => 'Tiada gambar diterima. Cuba sekali lagi.',
            'photo.image' => 'Fail itu bukan gambar.',
            'photo.max' => 'Gambar terlalu besar. Had ialah 25 MB.',
        ]);

        $file = $request->file('photo');

        if (! $this->storage->isSupported((string) $file->getMimeType())) {
            return response()->json([
                'message' => 'Format gambar ini tidak disokong. Guna JPEG atau PNG.',
            ], 422);
        }

        try {
            $stored = $this->storage->store($file);
        } catch (Throwable $e) {
            Log::error('Upload gambar gagal', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gambar gagal diproses. Cuba sekali lagi.',
            ], 500);
        }

        $photo = Photo::query()->create($stored + [
            'guest_name' => $this->cleanName($validated['guest_name'] ?? null),
            'caption' => $validated['caption'] ?? null,
            'upload_token' => $validated['upload_token'] ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Terima kasih! Gambar anda sudah masuk.',
            'photo' => [
                'id' => $photo->id,
                'name' => $photo->displayName(),
                'caption' => $photo->caption,
                'thumb' => $photo->thumbUrl(),
                'original' => $photo->originalUrl(),
                'ago' => 'baru sebentar tadi',
            ],
        ], 201);
    }

    private function cleanName(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name === '' ? null : $name;
    }
}
