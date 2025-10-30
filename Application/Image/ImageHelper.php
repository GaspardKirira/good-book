<?php

namespace Softadastra\Application\Image;

class ImageHelper
{
    public static function showImage($imagePath, $defaultImage = null)
    {
        if (is_null($defaultImage)) {
            $defaultImage = 'https://softadastra.com/public/images/default/softadastra.jpg';
        }

        if (!empty($imagePath) && file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
            return $imagePath;
        }

        return $defaultImage;
    }
}