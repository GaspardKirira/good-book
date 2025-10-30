<?php

namespace Domain\VendorShippingOptions;

use Domain\VendorShippingOptions\VendorShippingOption;

class VendorShippingOptionValidator
{
    static function validate(VendorShippingOption $vendor_shipping_option)
    {
        $errors = [];

        if ($error = self::validateName($vendor_shipping_option->getName())) {
            $errors['name'] = $error;
        }

        if ($error = self::validateAddress($vendor_shipping_option->getAddress())) {
            $errors['address'] = $error;
        }

        if ($error = self::validateDescription($vendor_shipping_option->getDescription())) {
            $errors['description'] = $error;
        }


        if ($error = self::validateImage($vendor_shipping_option->getImage())) {
            $errors['image'] = $error;
        }

        return $errors;
    }

    static function validateName($name)
    {
        if (empty($name)) {
            return 'The name cannot be empty.';
        }

        if (strlen($name) < 3) {
            return 'The name must be at least 3 characters long.';
        }

        return null;
    }

    static function validateAddress($address)
    {
        if (empty($address)) {
            return 'The address cannot be empty.';
        }
        return null;
    }

    static function validateDescription($description)
    {
        if (empty($description)) {
            return 'The description cannot be empty.';
        }

        if (strlen($description) < 3) {
            return 'The description must be at least 3 characters long.';
        }

        return null;
    }

    static function validateImage($photo)
    {
        if (strlen($photo) < 3) {
            return "Photo must be at least 3 characters long";
        }
        return null;
    }
}
