<?php

namespace Softadastra\Support;

use Cloudinary\Api\Upload\UploadApi;

final class CloudUpload
{
    /**
     * @return array<int,array{url:string, public_id:string}>
     */
    public static function manyProductImages(array $files, int $userId, int $productId, string $productTitle): array
    {
        if (empty($files) || !is_array($files)) {
            throw new \RuntimeException('No input files.');
        }

        $maxBytes    = 5 * 1024 * 1024;
        $mimeAllowed = ['image/jpeg', 'image/png', 'image/webp'];

        $baseFolder = defined('CLOUDINARY_FOLDER') ? trim(CLOUDINARY_FOLDER, '/') : 'softadastra/users';
        $folder     = preg_match('~/products$~', $baseFolder) ? $baseFolder : ($baseFolder . '/products');

        $out = [];

        foreach (($files['tmp_name'] ?? []) as $i => $tmp) {
            $err  = (int)($files['error'][$i] ?? UPLOAD_ERR_OK);
            $name = (string)($files['name'][$i] ?? 'image');

            if (empty($tmp) || $err === UPLOAD_ERR_NO_FILE) continue;
            if ($err !== UPLOAD_ERR_OK) throw new \RuntimeException("Upload error: {$name}");

            $size = (int)($files['size'][$i] ?? 0);
            if ($size <= 0 || $size > $maxBytes) throw new \RuntimeException("Invalid size for: {$name}");

            if (!is_uploaded_file($tmp)) throw new \RuntimeException("Invalid temp file: {$name}");

            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            $mime = $fi ? @finfo_file($fi, $tmp) : null;
            if ($fi) @finfo_close($fi);
            if (!$mime || !in_array($mime, $mimeAllowed, true)) {
                throw new \RuntimeException("Bad type: {$name}");
            }

            $prePublicId = sprintf('%s/%d/%d/img-%s', $folder, $userId, $productId, bin2hex(random_bytes(6)));

            $res = (new UploadApi())->upload($tmp, [
                'public_id'     => $prePublicId,
                'resource_type' => 'image',
                'overwrite'     => true,
                'invalidate'    => true,
                'eager'         => [['width' => 1024, 'height' => 1024, 'crop' => 'limit']],
                'context'       => ['caption' => $productTitle, 'alt' => $productTitle],
            ]);

            $url = $res['secure_url'] ?? null;
            if (!empty($res['eager'][0]['secure_url'])) {
                $url = $res['eager'][0]['secure_url'];
            }

            $pid = $res['public_id'] ?? $prePublicId;

            if (!$url || !$pid) {
                throw new \RuntimeException("Upload failed: {$name}");
            }

            $out[] = ['url' => (string)$url, 'public_id' => (string)$pid];
        }

        if (empty($out)) {
            throw new \RuntimeException('No image produced by uploader.');
        }
        return $out;
    }
}
