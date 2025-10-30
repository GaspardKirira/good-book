<?php

namespace Softadastra\Controllers;

use App\Response\JsonResponse;

use Domain\Users\UserHelper;
use Domain\Users\UserRepository;
use Exception;
use Softadastra\Application\Utils\FlashMessage;
use Softadastra\Model\GetUser;
use Softadastra\Services\ModerationService;

class UserController extends Controller
{

    private $path = 'users.account.';
    private $errors = 'errors.';

    public function legacyDashboard()
    {
        header('Location: /dashboard', true, 301);
        exit;
    }

    public function dashboard()
    {
        try {
            $userEntity = $this->getUserEntity();
            return $this->view($this->path . 'dashboard', compact('userEntity'));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function notifications()
    {
        try {
            $userEntity = $this->getUserEntity();
            return $this->generic($this->path . 'notifications', compact('userEntity'));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors');
        }
    }

    public function reportCounterfeit(int $productId, string $sellerId)
    {
        $auth = $this->getUserEntity();
        if (!$auth) return;

        $userRepository = new UserRepository();

        $db = $userRepository->getDb();
        $moderation = new ModerationService($db);

        $id = $moderation->flag(
            'product',
            'Image flagged: counterfeit',
            "seller:#{$sellerId}",
            'high',
            [
                'product_id' => $productId,
                'reason'     => 'possible counterfeit brand',
                'reported_by' => $auth->getId(),
            ]
        );

        return JsonResponse::success([
            'message' => 'Report submitted to moderation queue',
            'moderation_id' => $id,
        ]);
    }

    public function home()
    {
        try {
            return $this->shop($this->path . 'shop.home');
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function getUserJson()
    {
        $getUser = new GetUser();
        $user = $getUser->getUserEntity();
        if ($user) {
            $userPhoto = $user->getPhoto();
            $profileImage = UserHelper::getProfileImage($userPhoto);
            $followersCount = $this->getFollowersCount($user->getId());
            $followingCount = $this->getFollowingCount($user->getId());

            $notificationsCount = $this->getUnreadNotificationsCount($user->getId());

            $this->json([
                'id' => $user->getId(),
                'fullname' => $user->getFullname(),
                'email' => $user->getEmail(),
                'photo' => $profileImage,
                'status' => $user->getStatus(),
                'verified_email' => $user->getVerifiedEmail(),
                'messageCount' => $user->getMessageCount() ? $user->getMessageCount() : 0,
                'cover_photo' => $user->getCoverPhoto(),
                'created_at' => $user->getCreatedAt(),
                'bio' => $user->getBio(),
                'role' => $user->getRole(),
                'followers_count' => $followersCount ? $followersCount : 0,
                'following_count' => $followingCount ? $followingCount : 0,
                'notifications_count' => $notificationsCount ? $notificationsCount : 0,
                'username' => $user->getUsername()
            ], 200);
        } else {
            $this->json([
                'user' => null,
                'message' => 'User not found or token is invalid. Please log in.'
            ], 200);
        }
    }

    public function getUserById($id)
    {
        $repo = new UserRepository();
        $user = $repo->findById($id);

        if ($user) {
            $userPhoto = $user->getPhoto();
            $profileImage = UserHelper::getProfileImage($userPhoto);

            $followersCount    = $this->getFollowersCount($user->getId());
            $followingCount    = $this->getFollowingCount($user->getId());
            $notificationsCount = $this->getUnreadNotificationsCount($user->getId());

            return $this->json([
                'id'                 => $user->getId(),
                'fullname'           => $user->getFullname(),
                'email'              => $user->getEmail(),
                'photo'              => $profileImage,
                'status'             => $user->getStatus(),
                'verified_email'     => $user->getVerifiedEmail(),
                'messageCount'       => $user->getMessageCount() ?? 0,
                'cover_photo'        => $user->getCoverPhoto(),
                'created_at'         => $user->getCreatedAt(),
                'bio'                => $user->getBio(),
                'role'               => $user->getRole(),
                'followers_count'    => $followersCount ?? 0,
                'following_count'    => $followingCount ?? 0,
                'notifications_count' => $notificationsCount ?? 0,
                'username'           => $user->getUsername(),
            ], 200);
        }

        return $this->json([
            'user'    => null,
            'message' => "User with id {$id} not found."
        ], 404);
    }

    public function getUnreadNotificationsCount($userId)
    {
        $conn = $this->connectToDatabase();
        if ($conn === null) return 0;

        $sql = "SELECT COUNT(*) 
                FROM user_notifications 
                WHERE user_id = ? AND is_read = FALSE";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);

        mysqli_stmt_close($stmt);
        return $count;
    }

    public function getProfile(string $slug)
    {
        $slug = ltrim(urldecode($slug), '@');

        $userRepository = new UserRepository();
        $user = $userRepository->findByUsername($slug);

        if ($user) {
            $userPhoto      = $user->getPhoto();
            $profileImage   = UserHelper::getProfileImage($userPhoto);
            $followersCount = $this->getFollowersCount($user->getId());
            $followingCount = $this->getFollowingCount($user->getId());

            return $this->json([
                'id'               => $user->getId(),
                'fullname'         => $user->getFullname(),
                'email'            => $user->getEmail(),
                'photo'            => $profileImage,
                'status'           => $user->getStatus(),
                'verified_email'   => $user->getVerifiedEmail(),
                'messageCount'     => $user->getMessageCount() ?: 0,
                'cover_photo'      => $user->getCoverPhoto(),
                'created_at'       => $user->getCreatedAt(),
                'bio'              => $user->getBio(),
                'followers_count'  => $followersCount ?: 0,
                'following_count'  => $followingCount ?: 0,
                'phone'            => $user->getPhone(),
                'city'             => $user->getCityName() ?? null,
                'country'          => $user->getCountryName() ?? null,
                'country_image'    => $user->getCountryImageUrl() ?? null,
            ], 200);
        }

        return $this->json(['error' => 'User not found'], 404);
    }

    public function getSellerInfos()
    {
        $userRepository = new UserRepository();

        $emails = [];

        $data = [];

        foreach ($emails as $email) {
            $user = $userRepository->findByEmail($email);
            if ($user) {
                $userPhoto = $user->getPhoto();
                $profileImage = UserHelper::getProfileImage($userPhoto);
                $followersCount = $this->getFollowersCount($user->getId());

                $data[] = [
                    'id' => $user->getId(),
                    'fullname' => $user->getFullname(),
                    'username' => $user->getUsername(),
                    'photo' => $profileImage,
                    'followers_count' => $followersCount ? $followersCount : 0,
                ];
            }
        }

        $this->json([
            'data' => $data
        ], 200);
    }

    public function flashAPI()
    {
        $messages = FlashMessage::get();
        $response = [
            'status' => 'success',
            'messages' => [
                'success' => [],
                'error' => []
            ]
        ];

        foreach ($messages as $message) {
            if ($message['type'] === 'success') {
                $response['messages']['success'][] = $message['message'];
            } elseif ($message['type'] === 'error') {
                $response['messages']['error'][] = $message['message'];
            }
        }
        $this->json($response, 200);
    }
}
