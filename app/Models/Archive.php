<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Archive extends Model
{
    public const PENDING = 'pending';
    public const READY = 'ready';
    public const FAILED = 'failed';

    /** Gambar setiap ZIP. Majlis besar dipecah supaya tiada fail gergasi. */
    public const BATCH_SIZE = 200;

    protected $fillable = ['batch', 'status', 'path', 'bytes', 'photo_count', 'error'];

    protected $casts = [
        'batch' => 'integer',
        'bytes' => 'integer',
        'photo_count' => 'integer',
    ];

    public function isReady(): bool
    {
        return $this->status === self::READY
            && $this->path
            && Storage::disk('local')->exists($this->path);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->bytes;

        if ($bytes < 1048576) {
            return max(1, (int) round($bytes / 1024)) . ' KB';
        }

        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }

    public function deleteFile(): void
    {
        if ($this->path) {
            Storage::disk('local')->delete($this->path);
        }
    }
}
