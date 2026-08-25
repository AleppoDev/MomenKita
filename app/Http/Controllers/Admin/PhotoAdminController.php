<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class PhotoAdminController extends Controller
{
    /** Gambar setiap ZIP; memecahkan muat turun supaya tidak terlalu berat. */
    private const ZIP_BATCH = 200;

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

    /**
     * Muat turun semua gambar sebagai ZIP. Dipecahkan kepada beberapa kumpulan
     * kerana majlis besar boleh menghasilkan beberapa gigabait — satu fail
     * gergasi selalunya gagal separuh jalan.
     */
    public function downloadAll(Request $request): StreamedResponse
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'Sambungan PHP zip tidak aktif.');

        $batch = max(1, $request->integer('batch') ?: 1);
        $photos = Photo::query()
            ->orderBy('id')
            ->forPage($batch, self::ZIP_BATCH)
            ->get();

        abort_if($photos->isEmpty(), 404, 'Tiada gambar untuk kumpulan ini.');

        $disk = Storage::disk('public');
        $tempPath = tempnam(sys_get_temp_dir(), 'momenkita_');

        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);

        foreach ($photos as $photo) {
            if ($disk->exists($photo->original_path)) {
                $zip->addFile($disk->path($photo->original_path), $this->fileName($photo));
            }
        }

        $zip->close();

        $slug = Str::slug(Setting::get('couple_slug', 'momenkita'));
        $name = sprintf('%s-gambar-kumpulan-%d.zip', $slug, $batch);

        return response()->streamDownload(function () use ($tempPath) {
            $handle = fopen($tempPath, 'rb');

            while (! feof($handle)) {
                echo fread($handle, 1024 * 512);
                flush();
            }

            fclose($handle);
            @unlink($tempPath);
        }, $name, [
            'Content-Type' => 'application/zip',
        ]);
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
