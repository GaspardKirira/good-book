<?php

namespace Softadastra\Controllers\Account;

use Domain\Users\UserHelper;
use Domain\Users\UserRepository;
use Exception;
use Softadastra\Controllers\Controller;
use Softadastra\Model\GetUser;
use Softadastra\Model\JWT;

class SubscriptionController extends Controller
{
    private $path = 'users.subscription.';
    private $errors = 'errors.';
    private $jwt;
    private $token;

    public function __construct()
    {
        $this->jwt = new JWT();
        $this->token = $_COOKIE['token'] ?? null;
    }

    protected function getUserEntity()
    {
        $payload = $this->validateToken();
        if ($payload) {
            $userRepository = new UserRepository();
            return $userRepository->findById($payload['id']);
        }

        return null;
    }

    protected function validateToken()
    {
        if (isset($this->token) && $this->jwt->isValid($this->token) && !$this->jwt->isExpired($this->token) && $this->jwt->check($this->token, SECRET)) {
            return $this->jwt->getPayload($this->token);
        }
        return null;
    }

    private function followersCount($conn, int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM subscriptions WHERE following_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $cnt);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return (int)($cnt ?? 0);
    }

    public function subscribe()
    {
        $conn = $this->connectToDatabase();
        if ($conn === null) {
            $this->json(['error' => 'Database connection error.'], 500);
        }

        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        if (!isset($data['user_id'])) {
            $this->json(['error' => 'User ID is missing.'], 400);
        }

        $getUser = new GetUser();
        $user = $getUser->getUserEntity();
        if (!$user || !is_object($user)) {
            $this->json(['error' => 'Not connected'], 401);
        }

        $followerId  = (int)$user->getId();
        $followingId = (int)$data['user_id'];

        if ($followerId === $followingId) {
            $this->json(['error' => 'You cannot subscribe to yourself.'], 403);
        }

        // ⚡️ Idempotent + atomique : pas de pré-check, on s’appuie sur l’UNIQUE
        $sql = "INSERT INTO subscriptions (follower_id, following_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE following_id = VALUES(following_id)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $followerId, $followingId);

        if (!mysqli_stmt_execute($stmt)) {
            // 1062 (duplicate) ne devrait plus arriver avec ON DUP, mais on tolère en succès
            $errno = mysqli_errno($conn);
            mysqli_stmt_close($stmt);
            if ($errno !== 1062) {
                $this->json(['error' => 'Error during subscription.'], 500);
            }
        } else {
            mysqli_stmt_close($stmt);
        }

        // notif best-effort (ne bloque jamais la réponse)
        if ($notifStmt = mysqli_prepare(
            $conn,
            "INSERT INTO user_notifications (user_id, sender_id, notification_type, message)
         VALUES (?, ?, 'subscription', ?)"
        )) {
            $msg = "You have a new subscriber.";
            mysqli_stmt_bind_param($notifStmt, 'iis', $followingId, $followerId, $msg);
            @mysqli_stmt_execute($notifStmt);
            @mysqli_stmt_close($notifStmt);
        }

        $count = $this->followersCount($conn, $followingId);
        $this->json(['success' => true, 'followers_count' => $count], 200);
    }

    public function unsubscribe()
    {
        $conn = $this->connectToDatabase();
        if ($conn === null) {
            $this->json(['error' => 'Database connection error.'], 500);
        }

        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        if (!isset($data['user_id'])) {
            $this->json(['error' => 'User ID is missing.'], 400);
        }

        $user = $this->getUserEntity();
        if (!$user || !is_object($user)) {
            $this->json(['error' => 'Not connected'], 401);
        }

        $followerId  = (int)$user->getId();
        $followingId = (int)$data['user_id'];

        // ⚡️ Idempotent : on supprime si présent, sinon no-op
        $sql = "DELETE FROM subscriptions WHERE follower_id = ? AND following_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $followerId, $followingId);
        @mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $count = $this->followersCount($conn, $followingId);
        $this->json(['success' => true, 'followers_count' => $count], 200);
    }

    public function isFollowing($targetUserId)
    {
        $conn = $this->connectToDatabase();
        if ($conn === null) {
            $this->json(['error' => 'Database connection error.'], 500);
        }

        $getUser = new GetUser();
        $user = $getUser->getUserEntity();
        if (!$user || !is_object($user)) {
            $this->json(['following' => false], 200);
        }

        $followerId  = (int)$user->getId();
        $followingId = (int)$targetUserId;

        $sql = "SELECT 1 FROM subscriptions WHERE follower_id = ? AND following_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $this->json(['error' => 'Error preparing the query.'], 500);
        }
        mysqli_stmt_bind_param($stmt, 'ii', $followerId, $followingId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $isFollowing = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        $this->json(['following' => $isFollowing], 200);
    }

    public function followList(int $id, string $type = 'followers')
    {
        try {
            $userRepository = new UserRepository();
            $userEntity = $userRepository->findById($id);
            if (!$userEntity) {
                return $this->errors($this->errors . 'errors', ['errorMessage' => 'User not found.']);
            }
            return $this->followView($this->path . 'follow-list', compact('id', 'type', 'userEntity'));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors');
        }
    }

    public function getFollowers($id)
    {
        // Sanitize & validate
        $userId = (int) filter_var((string)$id, FILTER_SANITIZE_NUMBER_INT);
        if ($userId <= 0) {
            $this->json(['error' => 'Invalid user ID.'], 400);
            return;
        }

        $conn = $this->connectToDatabase();
        if ($conn === null) {
            $this->json(['error' => 'Database connection failed.'], 500);
            return;
        }

        // Followers de $userId = les users dont s.follower_id suit $userId (s.following_id = $userId)
        // On renvoie pour chaque user: followers_count & following_count via sous-requêtes indexées
        $sql = "SELECT 
                u.id, u.username, u.email, u.photo,
                c.name       AS country_name,
                c.image_url  AS country_image_url,
                ci.name      AS city_name,
                ul.show_city,
                -- Compteurs
                (SELECT COUNT(*) FROM subscriptions s2 WHERE s2.following_id = u.id) AS followers_count,
                (SELECT COUNT(*) FROM subscriptions s3 WHERE s3.follower_id  = u.id) AS following_count
            FROM subscriptions s
            JOIN users u               ON u.id = s.follower_id
            LEFT JOIN user_location ul ON ul.user_id = u.id
            LEFT JOIN countries c      ON c.id = ul.country_id
            LEFT JOIN cities ci        ON ci.id = ul.city_id
            WHERE s.following_id = ?
            ORDER BY s.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $this->json(['error' => 'Failed to prepare SQL statement.'], 500);
            return;
        }
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        if (!mysqli_stmt_execute($stmt)) {
            $this->json(['error' => 'Failed to execute SQL statement.'], 500);
            mysqli_stmt_close($stmt);
            return;
        }
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            $this->json(['error' => 'Failed to fetch results.'], 500);
            mysqli_stmt_close($stmt);
            return;
        }

        $followers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            if (!$row['show_city']) unset($row['city_name']);
            unset($row['show_city']);
            $row['photo'] = UserHelper::getProfileImage($row['photo']);
            // fallback sécurité
            $row['followers_count'] = (int)($row['followers_count'] ?? 0);
            $row['following_count'] = (int)($row['following_count'] ?? 0);
            $followers[] = $row;
        }
        mysqli_stmt_close($stmt);
        $this->json(['followers' => $followers], 200);
    }

    public function getFollowing($id)
    {
        // Sanitize & validate
        $userId = (int) filter_var((string)$id, FILTER_SANITIZE_NUMBER_INT);
        if ($userId <= 0) {
            $this->json(['error' => 'Invalid user ID.'], 400);
            return;
        }

        $conn = $this->connectToDatabase();
        if ($conn === null) {
            $this->json(['error' => 'Database connection failed.'], 500);
            return;
        }

        // Following de $userId = les users que $userId suit (s.following_id = u.id, s.follower_id = $userId)
        $sql = "SELECT 
                u.id, u.username, u.email, u.photo,
                c.name       AS country_name,
                c.image_url  AS country_image_url,
                ci.name      AS city_name,
                ul.show_city,
                (SELECT COUNT(*) FROM subscriptions s2 WHERE s2.following_id = u.id) AS followers_count,
                (SELECT COUNT(*) FROM subscriptions s3 WHERE s3.follower_id  = u.id) AS following_count
            FROM subscriptions s
            JOIN users u               ON u.id = s.following_id
            LEFT JOIN user_location ul ON ul.user_id = u.id
            LEFT JOIN countries c      ON c.id = ul.country_id
            LEFT JOIN cities ci        ON ci.id = ul.city_id
            WHERE s.follower_id = ?
            ORDER BY s.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $this->json(['error' => 'Failed to prepare SQL statement.'], 500);
            return;
        }
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        if (!mysqli_stmt_execute($stmt)) {
            $this->json(['error' => 'Failed to execute SQL statement.'], 500);
            mysqli_stmt_close($stmt);
            return;
        }
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            $this->json(['error' => 'Failed to fetch results.'], 500);
            mysqli_stmt_close($stmt);
            return;
        }

        $following = [];
        while ($row = mysqli_fetch_assoc($result)) {
            if (!$row['show_city']) unset($row['city_name']);
            unset($row['show_city']);
            $row['photo'] = UserHelper::getProfileImage($row['photo']);
            $row['followers_count'] = (int)($row['followers_count'] ?? 0);
            $row['following_count'] = (int)($row['following_count'] ?? 0);
            $following[] = $row;
        }
        mysqli_stmt_close($stmt);
        $this->json(['following' => $following], 200);
    }
}
