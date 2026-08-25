<?php

namespace App\Jobs;

use App\Models\Archive;
use App\Models\Photo;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Bina satu ZIP gambar asal di latar belakang.
 *
 * Dahulu ini berlaku di dalam permintaan web. Majlis 200 tetamu menghasilkan
 * beberapa gigabait; PHP kehabisan masa, pelayar berputus, dan pengantin
 * mendapat fail separuh siap tanpa sebarang amaran.
 */
class BuildPhotoArchive implements ShouldQueue
{
    use Queueable;

    /** Membina beberapa gigabait mengambil masa; jangan bunuh terlalu awal. */
    public int $timeout = 1800;

    public int $tries = 2;

    public function __construct(public int $archiveId)
    {
    }

    public function handle(): void
    {
        $archive = Archive::find($this->archiveId);

        if (! $archive) {
            return;
        }

        try {
            [$path, $bytes, $count] = $this->build($archive->batch);

            $archive->update([
                'status' => Archive::READY,
                'path' => $path,
                'bytes' => $bytes,
                'photo_count' => $count,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('Pembinaan arkib gagal', ['archive' => $archive->id, 'error' => $e->getMessage()]);

            $archive->update([
                'status' => Archive::FAILED,
                'error' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    /**
     * @return array{0:string,1:int,2:int}
     */
    private function build(int $batch): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Sambungan PHP zip tidak aktif.');
        }

        $photos = Photo::query()
            ->orderBy('id')
            ->forPage($batch, Archive::BATCH_SIZE)
            ->get();

        if ($photos->isEmpty()) {
            throw new RuntimeException('Tiada gambar dalam kumpulan ini.');
        }

        $public = Storage::disk('public');
        $local = Storage::disk('local');

        $slug = Str::slug(Setting::get('couple_slug', 'momenkita')) ?: 'momenkita';
        $relative = sprintf('archives/%s-kumpulan-%d-%s.zip', $slug, $batch, now()->format('Ymd-His'));

        $local->makeDirectory('archives');
        $absolute = $local->path($relative);

        $zip = new ZipArchive();

        if ($zip->open($absolute, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Fail ZIP tidak dapat dicipta.');
        }

        $added = 0;

        foreach ($photos as $photo) {
            if ($public->exists($photo->original_path)) {
                $zip->addFile($public->path($photo->original_path), $this->fileName($photo));
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            $local->delete($relative);

            throw new RuntimeException('Tiada fail gambar dijumpai pada cakera.');
        }

        return [$relative, (int) $local->size($relative), $added];
    }

    /** Nama fail yang bermakna untuk pengantin: susunan masa dan nama tetamu. */
    private function fileName(Photo $photo): string
    {
        $extension = pathinfo($photo->original_path, PATHINFO_EXTENSION) ?: 'jpg';
        $stamp = $photo->created_at?->format('Ymd-His') ?? 'tanpa-tarikh';
        $name = Str::slug($photo->displayName()) ?: 'tetamu';

        return sprintf('%04d_%s_%s.%s', $photo->id, $stamp, $name, $extension);
    }
}
