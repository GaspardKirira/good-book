<?php

namespace Domain\Users;

use App\Response\JsonResponse;
use Exception;
use Softadastra\Application\Image\PhotoHandler;

class UserFactory
{
    public const VALIDITY_TOKEN = 60 * 60 * 24 * 7;

    public static function createUserEntityFromGoogle($user)
    {
        $role = UserHelper::getRole();
        $status = UserHelper::getStatus();
        $cover = UserHelper::getCoverPhoto();
        $verified_email = $user->verifiedEmail ?? 0;

        return new User(
            $user->name ?? '',
            $user->email ?? '',
            $user->picture ?? '',
            '',
            $role,
            $status,
            $verified_email,
            $cover
        );
    }

    public static function createUserEntityFromSessionData($userData, $phone_number)
    {
        $role = UserHelper::getRole();
        $status = UserHelper::getStatus();
        $cover = UserHelper::getCoverPhoto();
        $bio = UserHelper::getBio();

        $user = new User(
            $userData['fullname'],
            $userData['email'],
            $userData['photo'],
            '',
            $role,
            $status,
            $userData['verified_email'],
            $cover
        );
        $user->setBio($bio);
        $user->setPhone($phone_number);

        return $user;
    }

    public static function createUserEntityFromDatabase($user)
    {
        $user = new User(
            $user->getFullname(),
            $user->getEmail(),
            $user->getPhoto(),
            $user->getPassword(),
            $user->getRole(),
            $user->getStatus(),
            $user->getVerifiedEmail(),
            $user->getCoverPhoto()
        );
        $user->setStatus('active');

        return $user;
    }

    public static function createUserEntityFromRegistration($fullname, $email, $password, $phone_number)
    {
        $photo = UserHelper::getPhoto();
        $role = UserHelper::getRole();
        $status = UserHelper::getStatus();
        $verified_email = UserHelper::getVerifiedEmail();
        $cover = UserHelper::getCoverPhoto();
        $bio = UserHelper::getBio();

        $user = new User($fullname, $email, $photo, $password, $role, $status, $verified_email, $cover);
        $user->setBio($bio);
        $user->setPhone($phone_number);

        return $user;
    }

    public static function getUserRegistrationData($userEntity)
    {
        return [
            'fullname' => $userEntity->getFullname(),
            'email' => $userEntity->getEmail(),
            'photo' => $userEntity->getPhoto(),
            'role' => $userEntity->getRole(),
            'status' => $userEntity->getStatus(),
            'verified_email' => $userEntity->getVerifiedEmail(),
            'cover_photo' => $userEntity->getCoverPhoto(),
            'bio' => $userEntity->getBio(),
        ];
    }

    public static function finalizeUserRegistration(UserRepository $repository, $user)
    {
        $_SESSION['unique_id'] = $user->getId();
        $token = UserHelper::token($user, self::VALIDITY_TOKEN);
        setcookie('token', $token, time() + self::VALIDITY_TOKEN, '/', '', true, true);
        $user->setAccessToken($token);
        $repository->updateAccessToken($user);
        unset($_SESSION['user_registration']);
    }

    public static function isInvalidCsrfToken($post)
    {
        return empty($post['csrf_token']) || $post['csrf_token'] !== $_SESSION['csrf_token'];
    }

    public static function validateEmailAvailability(UserRepository $repository, $email)
    {
        if ($repository->findByEmail($email)) {
            throw new Exception("This email is already taken.");
        }
    }

    public static function updateUserPhoto(UserRepository $repository, $photo, $user_id, $type)
    {
        $photoPath = self::handleImage($photo, 'public/images/' . $type, $type);

        if ($photoPath && strpos($photoPath, 'error') === false) {
            $field = ($type === 'profile') ? 'photo' : 'cover_photo';
            $repository->updateField($user_id, $field, $photoPath);
            return JsonResponse::handleSuccess(ucfirst($type) . ' photo mise à jour avec succès');
        } else {
            return JsonResponse::handleError($photoPath);
        }
    }

    public static function handleImage($file, $directory, $prefix = 'softadastra')
    {
        return PhotoHandler::photo($file, $prefix, $directory);
    }
}