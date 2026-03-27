<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\File;

class FileService
{
    public function save($model, $file, $file_type_id, $file_subtype_id, $disk = 'public', $folder = 'files')
    {
        $path = Storage::disk($disk)->putFile($folder, $file);

        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'original_extension' => $file->getClientOriginalExtension(),
            'client_mime' => $file->getClientMimeType(),
            'mime' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'hash_md5' => md5_file($file->getPathname()),
            'hash_sha256' => hash_file('sha256', $file->getPathname()),
            'uploaded_at' => now()->toDateTimeString(),
        ];

        if (str_starts_with($file->getMimeType(), 'image/')) {
            [$width, $height] = getimagesize($file->getPathname());
            $metadata['image'] = compact('width', 'height');
        }

        return File::updateOrCreate(
            [
                'model_id' => $model->id,
                'model_type' => get_class($model),
                'file_type_id' => $file_type_id,
                'file_subtype_id' => $file_subtype_id,
            ],
            [
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'path' => $path,
                'mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'metadata' => $metadata,
            ]
        );
    }
}
