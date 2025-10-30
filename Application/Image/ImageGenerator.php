<?php

namespace Softadastra\Application\Image;

class ImageGenerator
{
    public function create(array $files, string $uploadDirectory = '')
    {
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $maxFileSize = 5 * 1024 * 1024;
        $photo['images'] = '';

        foreach ($files['p_featured_photo']['name'] as $key => $name) {
            if ($files['p_featured_photo']['error'][$key] !== UPLOAD_ERR_OK) {
                echo "Une erreur est survenue lors de l'envoi du fichier $name.";
                continue;
            }

            if (!in_array($files['p_featured_photo']['type'][$key], $allowedTypes)) {
                echo "Le fichier $name n'est pas autorisé.";
                continue;
            }

            if ($files['p_featured_photo']['size'][$key] > $maxFileSize) {
                echo "Le fichier $name dépasse la taille maximale autorisée.";
                continue;
            }

            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $uniqueName = uniqid('img_', true) . '.' . $extension;

            $uploadFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $uniqueName;
            if (!move_uploaded_file($files['p_featured_photo']['tmp_name'][$key], $uploadFilePath)) {
                echo "Une erreur est survenue lors de la sauvegarde du fichier $name.";
            } else {
                $photo['images'] .= $uploadFilePath . ',';
            }
        }
        $photo['images'] = rtrim($photo['images'], ',');
        return $photo['images'];
    }

    public function profil(array $file, string $uploadDirectory = '')
    {
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $maxFileSize = 5 * 1024 * 1024;
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo "Une erreur est survenue lors de l'envoi du fichier.";
            return false;
        }
        if (!in_array($file['type'], $allowedTypes)) {
            echo "Le fichier n'est pas autorisé.";
            return false;
        }
        if ($file['size'] > $maxFileSize) {
            echo "Le fichier dépasse la taille maximale autorisée.";
            return false;
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid('profile_', true) . '.' . $extension;
        $uploadFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            echo "Une erreur est survenue lors de la sauvegarde du fichier.";
            return false;
        }
        return $uploadFilePath;
    }

    static public function image(array $file, string $uploadDirectory = '')
    {
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $maxFileSize = 5 * 1024 * 1024;
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo "Une erreur est survenue lors de l'envoi du fichier.";
            return false;
        }
        if (!in_array($file['type'], $allowedTypes)) {
            echo "Le fichier n'est pas autorisé.";
            return false;
        }
        if ($file['size'] > $maxFileSize) {
            echo "Le fichier dépasse la taille maximale autorisée.";
            return false;
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid('adastra_', true) . '.' . $extension;
        $uploadFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            echo "Une erreur est survenue lors de la sauvegarde du fichier.";
            return false;
        }
        return $uploadFilePath;
    }

    static public function photo(array $file, string $prefix = 'softadastra', string $uploadDirectory = '')
    {
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $maxFileSize = 5 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo "Une erreur est survenue lors de l'envoi du fichier.";
            return false;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            echo "Le fichier n'est pas autorisé.";
            return false;
        }

        if ($file['size'] > $maxFileSize) {
            echo "Le fichier dépasse la taille maximale autorisée.";
            return false;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid($prefix . '_', true) . '.' . $extension;
        $uploadFilePath = rtrim($uploadDirectory, '/') . '/' . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            echo "Une erreur est survenue lors de la sauvegarde du fichier.";
            return false;
        }
        return $uniqueName;
    }
}
