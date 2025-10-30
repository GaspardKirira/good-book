<?php

namespace Softadastra\Application\Utils;

class StringHelper
{
    public static function getExtrait(string $description, int $limit = 50)
    {
        $cleanDescription = strip_tags($description);
        if (strlen($cleanDescription) <= $limit) {
            return $cleanDescription;
        }
        $last_space_pos = strrpos(substr($cleanDescription, 0, $limit), ' ');
        return substr($cleanDescription, 0, $last_space_pos) . '...';
    }
}
