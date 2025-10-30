<?php

namespace Softadastra\Support;

final class CloudinaryUrl
{
    public static function make(string $publicId, array $t = []): string
    {
        $cloud = CLOUDINARY_CLOUD_NAME;
        $parts = ['f_auto', 'q_auto'];
        if (isset($t['w'])) $parts[] = 'w_' . (int)$t['w'];
        if (isset($t['h'])) $parts[] = 'h_' . (int)$t['h'];
        if (isset($t['c'])) $parts[] = 'c_' . $t['c'];
        if (isset($t['g'])) $parts[] = 'g_' . $t['g'];
        return "https://res.cloudinary.com/{$cloud}/image/upload/" . implode(',', $parts) . "/{$publicId}";
    }
    public static function avatar(?string $pid): ?string
    {
        return $pid ? self::make($pid, ['w' => 256, 'h' => 256, 'c' => 'fill', 'g' => 'auto']) : null;
    }
    public static function cover(?string $pid): ?string
    {
        return $pid ? self::make($pid, ['w' => 1600, 'h' => 600, 'c' => 'fill', 'g' => 'auto']) : null;
    }
}
