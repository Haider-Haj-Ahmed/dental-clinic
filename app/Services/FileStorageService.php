<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'local');
    }

    /**
     * Store an uploaded file and return metadata needed for the DB row.
     *
     * @return array{path: string, size_kb: int, mime_type: string}
     */
    public function store(UploadedFile $file, string $folder): array
    {
        $extension = $file->getClientOriginalExtension();
        $filename  = Str::uuid().'.'.$extension;
        $path      = $folder.'/'.$filename;

        Storage::disk($this->disk)->putFileAs($folder, $file, $filename);

        return [
            'path'      => $path,
            'size_kb'   => (int) ceil($file->getSize() / 1024),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
        ];
    }

    /**
     * Delete a file from disk. Fails silently if the file is missing.
     */
    public function delete(string $path): void
    {
        Storage::disk($this->disk)->delete($path);
    }

    /**
     * Return a temporary URL (S3) or a signed route URL (local disk).
     * Valid for 30 minutes.
     */
    public function temporaryUrl(string $path): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(30));
        }

        // For local disk — serve via the signed storage route built into Laravel 11
        return Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes(30));
    }

    /**
     * Check whether a file exists on disk.
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }
}
