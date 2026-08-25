<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    protected $fillable = [
        'guest_name', 'caption', 'original_path', 'thumb_path',
        'mime', 'bytes', 'width', 'height', 'upload_token', 'ip',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'bytes' => 'integer',
    ];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    public function displayName(): string
    {
        return trim((string) $this->guest_name) !== '' ? $this->guest_name : 'Tetamu';
    }

    public function thumbUrl(): string
    {
        return Storage::disk('public')->url($this->thumb_path);
    }

    public function originalUrl(): string
    {
        return Storage::disk('public')->url($this->original_path);
    }

    /** Saiz asal dalam bentuk mesra pembaca, cth "2.4 MB". */
    public function humanSize(): string
    {
        $bytes = (int) $this->bytes;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    /** Buang fail asal dan thumbnail dari cakera. */
    public function deleteFiles(): void
    {
        Storage::disk('public')->delete(array_filter([$this->original_path, $this->thumb_path]));
    }
}
