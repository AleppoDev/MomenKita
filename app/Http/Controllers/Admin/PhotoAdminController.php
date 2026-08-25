<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BuildPhotoArchive;
use App\Models\Archive;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PhotoAdminController extends Controller
{
    public function toggle(Photo $photo)
    {
        $photo->update(['is_hidden' => ! $photo->is_hidden]);

        return back()->with('status', $photo->is_hidden ? 'Gambar disembunyikan.' : 'Gambar dipaparkan semula.');
    }

    public function destroy(Photo $photo)
    {
        $photo->deleteFiles();
        $photo->delete();

        return back()->with('status', 'Gambar dibuang.');
    }

    /** Muat turun satu gambar dalam resolusi asal. */
    public function download(Photo $photo): BinaryFileResponse
    {
        $disk = Storage::disk('public');

        abort_unless($disk->exists($photo->original_path), 404);

        return response()->download($disk->path($photo->original_path), $this->fileName($photo));
    }

    /** Minta satu ZIP dibina di latar belakang. */
    public function requestArchive(Request $request)
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'Sambungan PHP zip tidak aktif.');

        $batch = max(1, $request->integer('batch') ?: 1);

        $archive = Archive::query()->create([
            'batch' => $batch,
            'status' => Archive::PENDING,
        ]);

        BuildPhotoArchive::dispatch($archive->id)->onQueue('archives');

        return back()->with('status', 'ZIP sedang disediakan. Muat semula halaman ini sebentar lagi.');
    }

    /**
     * Hantar ZIP yang sudah siap. Ia disimpan pada cakera peribadi, jadi
     * satu-satunya jalan masuk ialah laluan ini yang berada di belakang
     * log masuk admin.
     */
    public function downloadArchive(Archive $archive): BinaryFileResponse
    {
        abort_unless($archive->isReady(), 404);

        return response()->download(
            Storage::disk('local')->path($archive->path),
            basename($archive->path)
        );
    }

    public function destroyArchive(Archive $archive)
    {
        $archive->deleteFile();
        $archive->delete();

        return back()->with('status', 'Arkib dibuang.');
    }

    /** Nama fail yang bermakna untuk pengantin: susunan masa + nama tetamu. */
    private function fileName(Photo $photo): string
    {
        $extension = pathinfo($photo->original_path, PATHINFO_EXTENSION) ?: 'jpg';
        $stamp = $photo->created_at?->format('Ymd-His') ?? 'tanpa-tarikh';
        $name = Str::slug($photo->displayName()) ?: 'tetamu';

        return sprintf('%04d_%s_%s.%s', $photo->id, $stamp, $name, $extension);
    }
}
