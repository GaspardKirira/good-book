<?php

namespace Domain\Countries;

class CountryValidator
{
    static function validate(Country $entity)
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
