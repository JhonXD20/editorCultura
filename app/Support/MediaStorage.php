<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MediaStorage
{
    public static function store(UploadedFile $file, int $userId): MediaAsset
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $extension = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            'image/gif' => 'gif', 'video/mp4' => 'mp4', 'video/webm' => 'webm',
        ][$mime] ?? null;
        if ($extension === null) {
            throw ValidationException::withMessages(['file' => 'O conteúdo real do arquivo não é permitido.']);
        }
        $kind = str_starts_with($mime, 'video/') ? 'video' : 'image';
        $size = filesize($file->getRealPath());
        $limit = ($kind === 'image' ? 10 : 200) * 1024 * 1024;
        if ($size === false || $size > $limit) {
            throw ValidationException::withMessages(['file' => 'O arquivo excede o tamanho permitido.']);
        }
        $metadata = [];
        if ($kind === 'image') {
            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions === false) {
                throw ValidationException::withMessages(['file' => 'A imagem não pôde ser validada.']);
            }
            $metadata = ['width' => $dimensions[0], 'height' => $dimensions[1]];
        }
        $uuid = (string) Str::uuid();
        $path = $file->storeAs("media/{$kind}s", $uuid.'.'.$extension, 'public');

        return MediaAsset::create([
            'uuid' => $uuid, 'enviado_por' => $userId, 'disco' => 'public', 'caminho' => $path,
            'nome_original' => $file->getClientOriginalName(), 'tipo_mime' => $mime, 'tamanho' => $size,
            'hash_verificacao' => hash_file('sha256', $file->getRealPath()), 'tipo' => $kind, 'metadados' => $metadata,
        ]);
    }
}
