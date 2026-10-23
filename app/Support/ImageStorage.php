<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Images of projects and tasks are stored on the "public" disk, each one in
 * its own random directory (e.g. project/Ab12.../file.png).
 */
class ImageStorage
{
    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder.'/'.Str::random(), 'public');
    }

    public static function delete(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $directory = dirname($path);

        // Only ever remove a "<folder>/<random>" directory created by store().
        if (preg_match('#^(project|task)/[A-Za-z0-9]+$#', $directory)) {
            Storage::disk('public')->deleteDirectory($directory);
        }
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk('public')->url($path);
    }
}
