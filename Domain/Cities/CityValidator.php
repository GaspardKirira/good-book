<?php

namespace Domain\Cities;

use Domain\Cities\City;

class CityValidator
{
    static function validate(City $entity)
    {
        $errors = [];

        if ($error = self::validateName($entity->getName())) {
            $errors['name'] = $error;
        }

        return $errors;
    }

    static function validateName($name)
    {
        if (empty($name)) {
            return 'The name cannot be empty.';
        }

        return null;
    }
}
