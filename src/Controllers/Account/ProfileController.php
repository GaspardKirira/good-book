<?php

namespace Softadastra\Controllers\Account;

use Domain\Users\UserRepository;
use Domain\Users\UserService;
use Domain\Users\UserValidator;
use Exception;
use Softadastra\Controllers\Controller;
use Softadastra\Model\GetUser;

class ProfileController extends Controller
{
    private $path = 'users.account.';
    private $errors = 'errors.';

    public function postEditProfile()
    {
        $userRepository = new UserRepository();
        $userService = new UserService($userRepository);
        $userService->updateUser($_POST);
    }

    // NEW: Profil public /@username
    public function publicProfile(string $slug)
    {
        try {
            // Sécurité/validation légère (YouTube-like): a-z, 0-9, ., _, -
            if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $slug)) {
                return $this->errors($this->errors . 'errors', ['errorMessage' => 'Invalid username']);
            }

            $repo = new UserRepository();
            $userEntity = $repo->findByUsername($slug);
            if (!$userEntity) {
                return $this->errors($this->errors . 'errors', ['errorMessage' => 'User not found']);
            }

            // View publique inchangée (tu peux garder users.profile)
            return $this->view($this->path . 'profile', compact('userEntity'));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    // Legacy: /profile/:slug → redirige en 301 vers /@username
    public function myProfile(string $slug)
    {
        try {
            $repo = new UserRepository();
            $userEntity = $repo->findByUsername($slug);
            if (!$userEntity) {
                return $this->errors($this->errors . 'errors', ['errorMessage' => 'User not found']);
            }

            // Canonical 301 vers la nouvelle forme
            $this->redirect('/@' . rawurlencode($userEntity->getUsername()), 301);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function updatePhoto()
    {
        try {
            $userRepository = new UserRepository();
            $userService = new UserService($userRepository);
            $userService->updatePhoto($_FILES);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function updatePassword()
    {
        $getUser = new GetUser();
        $user = $getUser->getUserEntity();
        $userId = $user->getId();

        if (!$userId) {
            $this->json(['error' => 'User not logged in.'], 401);
        }

        $currentPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            $this->json(['error' => 'Passwords do not match.'], 422);
        }

        $errorPwd = UserValidator::validatePassword($newPassword);
        if ($errorPwd) {
            $this->json(['error' => $errorPwd], 422);
        }

        $user->setId($userId);
        $userRepository = new UserRepository();

        try {
            $success = $userRepository->updatePassword($user, $currentPassword, $newPassword);
            if ($success) {
                $this->json(['success' => 'Password successfully updated.'], 200);
            } else {
                $this->json(['error' => 'Password update failed.'], 500);
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Le mot de passe actuel est incorrect.') !== false) {
                $this->json(['error' => 'Current password is incorrect.'], 403);
            } else {
                $this->json(['error' => 'An error occurred.'], 500);
            }
        }
    }
}
