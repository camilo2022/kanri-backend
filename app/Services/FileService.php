<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class FileService
{
    public function save($model, $file, $file_type_id, $file_subtype_id, $disk = 'public', $folder = 'files')
    {
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $file = $this->process_image($file);
        }

        $path = Storage::disk($disk)->putFile($folder, $file);
        $storage_path = storage_path($disk.'/storage/'.$path);
        $this->corregirOrientacion($storage_path);

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

        // Un archivo por modelo/tipo/subtipo: al subir otro se reemplaza el anterior (si el nombre fuera parte de la
        // identidad se crearía otro registro y la relación seguiría mostrando el viejo)
        $previous = File::query()
            ->where('model_id', $model->id)
            ->where('model_type', get_class($model))
            ->where('file_type_id', $file_type_id)
            ->where('file_subtype_id', $file_subtype_id)
            ->first();

        if ($previous && $previous->path && $previous->path !== $path) {
            Storage::disk($disk)->delete($previous->path);
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

    // A diferencia de save, siempre crea un archivo nuevo: un registro puede tener varios del mismo subtipo y settings dice a qué pertenece cada uno
    public function add($model, $file, $file_type_id, $file_subtype_id, array $settings, $disk = 'public', $folder = 'files', ?string $file_name = null)
    {
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $file = $this->process_image($file);
        }

        $path = $file_name
            ? Storage::disk($disk)->putFileAs($folder, $file, $file_name.'.'.$file->getClientOriginalExtension())
            : Storage::disk($disk)->putFile($folder, $file);
        $storage_path = storage_path($disk.'/storage/'.$path);
        $this->corregirOrientacion($storage_path);

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

        return File::create([
            'model_id' => $model->id,
            'model_type' => get_class($model),
            'file_type_id' => $file_type_id,
            'file_subtype_id' => $file_subtype_id,
            'name' => $file_name ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'metadata' => $metadata,
            'settings' => $settings,
        ]);
    }

    public function remove(File $file, $disk = 'public')
    {
        Storage::disk($disk)->delete($file->getRawOriginal('path'));
        $file->delete();
    }

    public function save_document($model, $file, $file_type_id, $file_subtype_id, $disk = 'public', $folder = 'files')
    {
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $file = $this->process_image($file);
        }

        $path = Storage::disk($disk)->putFile($folder, $file);
        $storage_path = storage_path($disk.'/storage/'.$path);
        $this->corregirOrientacion($storage_path);

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

    private function corregirOrientacion($rutaImagen)
    {
        $info = pathinfo($rutaImagen);
        if (strtolower($info['extension']) != 'jpg' && strtolower($info['extension']) != 'jpeg') {
            return false;
        }

        $exif = @exif_read_data($rutaImagen);
        if (! $exif || ! isset($exif['Orientation'])) {
            return false;
        }

        $orientacion = $exif['Orientation'];

        if ($orientacion == 1) {
            return true;
        }

        $img = imagecreatefromjpeg($rutaImagen);

        switch ($orientacion) {
            case 3:
                $img = imagerotate($img, 180, 0);
                break;
            case 6:
                $img = imagerotate($img, -90, 0);
                break;
            case 8:
                $img = imagerotate($img, 90, 0);
                break;
            default:
                return false;
        }

        imagejpeg($img, $rutaImagen, 90);
        imagedestroy($img);

        return true;
    }

    /**
     * Las imágenes (JPG, PNG o WebP) se guardan siempre como WebP: pesan menos y el servidor carga menos. Se reducen a
     * un máximo de 1000 x 1000 px sin ampliar las pequeñas. Intervention corrige la orientación EXIF al leer la imagen.
     */
    private function process_image($file)
    {
        $path = $file->getPathname();

        if (! in_array(mime_content_type($path), ['image/jpeg', 'image/png', 'image/webp', 'image/avif'], true)) {
            return $file;
        }

        // Abrir la imagen ocupa unos 4 bytes por píxel: una más grande que el límite agota la memoria y no se puede atrapar
        [$width, $height] = @getimagesize($path) ?: [0, 0];
        if ($width > File::MAX_IMAGE_SIDE || $height > File::MAX_IMAGE_SIDE) {
            throw ValidationException::withMessages([
                'file' => [__('validation.dimensions', ['max_width' => File::MAX_IMAGE_SIDE, 'max_height' => File::MAX_IMAGE_SIDE])],
            ]);
        }

        try {
            $webp = $this->convertir_webp(file_get_contents($path));

            // En una carpeta de la aplicación: Apache no puede escribir en la temporal del sistema (C:/Windows/Temp)
            $folder = storage_path('app/tmp');
            is_dir($folder) || mkdir($folder, 0755, true);
            $temp = tempnam($folder, 'img');
            file_put_contents($temp, $webp);
            register_shutdown_function(fn () => @unlink($temp));
        } catch (\Throwable $e) {
            // Si la imagen no se puede procesar se guarda tal como llegó
            return $file;
        }

        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.webp';

        return new UploadedFile($temp, $name, 'image/webp', null, true);
    }

    private function convertir_webp(string $contents): string
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->decodeBinary($contents);

        // Redimensionar proporcionalmente, sin ampliar imágenes pequeñas.
        $image->scaleDown(width: 1000, height: 1000);

        return $image->encodeUsingFormat(Format::WEBP, quality: 80)->toString();
    }
}
