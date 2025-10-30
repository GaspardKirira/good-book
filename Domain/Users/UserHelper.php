<?php

namespace Domain\Users;

use Exception;
use Softadastra\Model\JWT;

class UserHelper
{
    static public function getProfileImage($userPhoto)
    {
        $defaultAvatar = '/public/images/profile/avatar.jpg';

        if (!empty($userPhoto)) {
            if (filter_var($userPhoto, FILTER_VALIDATE_URL)) {
                if (strpos($userPhoto, 'googleusercontent.com') !== false) {
                    $headers = @get_headers($userPhoto);
                    if ($headers && strpos($headers[0], '200') !== false) {
                        return $userPhoto;
                    }
                } else {
                    return $userPhoto;
                }
            } else {
                $localPath = $_SERVER['DOCUMENT_ROOT'] . '/public/images/profile/' . $userPhoto;
                if (file_exists($localPath)) {
                    return '/public/images/profile/' . $userPhoto;
                }
            }
        }

        return $defaultAvatar;
    }

    static function token($user, $validity)
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        $payload = [
            'id' => $user->getId(),
            'name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ];
        $jwt = new JWT();
        $token = $jwt->generate($header, $payload, SECRET, $validity);

        return $token;
    }

    static public function getPhoto()
    {
        return 'avatar.jpg';
    }

    static public function getRoleName(): string
    {
        return 'user';
    }
    static public function getRole(): string
    {
        return self::getRoleName();
    }

    static public function getStatus()
    {
        return 'active';
    }

    static public function getVerifiedEmail()
    {
        return 0;
    }

    static public function getCoverPhoto()
    {
        return 'cover.jpg';
    }

    static public function getBio()
    {
        return 'This user has not added a bio yet.';
    }

    static public function getAdmin()
    {
        return 'admin';
    }

    static public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    static public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    static function verify_email(UserRepository $repo, $email)
    {
        if ($repo->findByEmail($email)) {
            return true;
        }
        return false;
    }

    static function while($errors)
    {
        foreach ($errors as $field => $error) {
            echo "Erreur sur le champ $field : $error<br>";
        }
    }

    static function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    static function verifyCsrfToken($token)
    {
        if (empty($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
            throw new \Exception('Invalid CSRF token');
        }
    }

    static function redirectTo($path)
    {
        header('Location: ' . $path);
        exit;
    }

    static function getFlashMessage()
    {
        if (!empty($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }

    static function setFlashMessage($message)
    {
        $_SESSION['flash_message'] = $message;
    }

    static function getFlashError()
    {
        if (!empty($_SESSION['flash_error'])) {
            $error = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
            return $error;
        }
        return null;
    }

    static function setFlashError($error)
    {
        $_SESSION['flash_error'] = $error;
    }

    static function getPhotoPath($photo)
    {
        return $photo;
    }

    static function getCoverPhotoPath($photo)
    {
        return $photo;
    }

    static function getPhotoName($photo)
    {
        return basename($photo);
    }

    static function getCoverPhotoName($photo)
    {
        return basename($photo);
    }

    static function getPhotoExtension($photo)
    {
        return pathinfo($photo, PATHINFO_EXTENSION);
    }

    static function getCoverPhotoExtension($photo)
    {
        return pathinfo($photo, PATHINFO_EXTENSION);
    }

    static function getPhotoSize($photo)
    {
        return filesize($photo);
    }

    static function getCoverPhotoSize($photo)
    {
        return filesize($photo);
    }

    static function getPhotoType($photo)
    {
        return mime_content_type($photo);
    }

    static public function lastName($fullName)
    {
        $parts = explode(' ', $fullName);
        if (count($parts) > 1) {
            array_shift($parts);
            return implode(' ', $parts);
        }
        return '';
    }

    static public function formatFullName($fullName)
    {
        $fullName = preg_replace('/\s+/', ' ', trim($fullName));
        $parts = explode(' ', $fullName);
        if (count($parts) > 2) {
            $parts = array_slice($parts, 0, 2);
        }
        $formatted = ucwords(strtolower(implode(' ', $parts)));

        return $formatted;
    }

    static public function formatUsername($username)
    {
        $username = preg_replace('/[^a-z0-9]/', '', strtolower($username));
        return $username;
    }

    static public function generateUsername($fullName, UserRepository $userRepository)
    {
        $parts = preg_split('/\s+/', trim($fullName));
        $firstTwo = array_slice($parts, 0, 2);
        $usernameBase = strtolower(implode('', $firstTwo));
        $username = self::formatUsername($usernameBase);

        $uniqueUsername = $username;
        $counter = 1;

        while (self::isUsernameTaken($userRepository, $uniqueUsername)) {
            $uniqueUsername = $username . $counter;
            $counter++;
        }

        return $uniqueUsername;
    }

    static public function isUsernameTaken(UserRepository $userRepository, $username)
    {
        $user = $userRepository->findByUsername($username);
        return $user !== null;
    }
}
