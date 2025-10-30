<?php

namespace Softadastra\Controllers;

use Domain\Users\UserRepository;
use Exception;
use Softadastra\Application\Http\RedirectionHelper;
use Softadastra\Application\Image\PhotoHandler;
use Softadastra\Config\Database;
use Softadastra\Model\GetUserCookie;
use Softadastra\Model\JWT;

class Controller
{
    public $pdo;

    private $jwt;
    private $token;

    private ?object $meCache = null;

    public function __construct()
    {
        $this->pdo = Database::getInstance(DB_NAME, DB_HOST, DB_USER, DB_PWD);
        $this->jwt = new JWT();
        $this->token = $_COOKIE['token'] ?? null;
    }

    private function render(string $path, string $layout, ?array $params = null): void
    {
        ob_start();

        $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
        $filePath = VIEWS . $path . '.php';

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "View not found: $filePath";
            exit;
        }

        if (is_array($params)) {
            extract($params);
        }

        require $filePath;

        $content = ob_get_clean();

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            echo $content;
            return;
        }

        require VIEWS . $layout;
    }

    public function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }

    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function jsonUnauthorized(string $msg = 'Unauthorized'): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $msg]);
        exit;
    }

    protected function requireRole(string $roleName, $user = null): void
    {
        $user ??= $this->getUserEntity();
        $name = method_exists($user, 'getRoleName') ? $user->getRoleName() : null;
        if ($name !== $roleName) {
            $this->jsonUnauthorized('Insufficient role');
        }
    }

    protected function requireAnyRole(array $roleNames, $user = null): void
    {
        $user ??= $this->getUserEntity();
        $name = method_exists($user, 'getRoleName') ? $user->getRoleName() : null;
        $all  = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [];

        $ok = ($name && in_array($name, $roleNames, true));
        if (!$ok && $all) {
            $ok = count(array_intersect($roleNames, $all)) > 0;
        }
        if (!$ok) {
            $this->jsonUnauthorized('Insufficient role');
        }
    }

    protected function userHasAnyRole(array $roleNames, $user = null): bool
    {
        $user ??= $this->getUserEntity();
        if (!$user) return false;
        $name = method_exists($user, 'getRoleName') ? $user->getRoleName() : null;
        $all  = method_exists($user, 'getRoleNames') ? (array)$user->getRoleNames() : [];
        return ($name && in_array($name, $roleNames, true)) || ($all && count(array_intersect($roleNames, $all)) > 0);
    }

    protected function userVal(?object $me, array $getterNames, array $propNames)
    {
        if (!$me) return null;

        foreach ($getterNames as $g) {
            if (method_exists($me, $g)) {
                try {
                    return $me->{$g}();
                } catch (\Throwable $e) {
                }
            }
        }
        foreach ($propNames as $p) {
            if (isset($me->{$p})) return $me->{$p};
        }
        return null;
    }

    protected function getCurrentUserOrNull()
    {
        if ($this->meCache !== null) return $this->meCache;

        $getter = new GetUserCookie();
        $user = $getter->getUserEntity();

        return $this->meCache = $user;
    }

    protected function getUserEntity()
    {
        $payload = $this->validateToken();
        if ($payload) {
            $userRepository = new UserRepository();
            return $userRepository->findById((int)$payload['id']);
        }
        if ($this->isAjax()) {
            $this->jsonUnauthorized('Authentication required');
        }
        RedirectionHelper::redirect('login');
        exit;
    }

    protected function resolveAgencyIdForUser($user): int
    {
        $payload = $this->validateToken();
        if (!empty($payload['agency_id'])) return (int)$payload['agency_id'];

        $uid = (int)$user->getId();
        $pdo = $this->pdo->getPdo();

        try {
            $stmt = $pdo->prepare("SELECT agency_id FROM agency_users WHERE user_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $uid]);
            $aid = (int)($stmt->fetchColumn() ?: 0);
            if ($aid > 0) return $aid;
        } catch (\Throwable $e) {
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM vendor_shipping_options WHERE owner_user_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $uid]);
            $aid = (int)($stmt->fetchColumn() ?: 0);
            if ($aid > 0) return $aid;
        } catch (\Throwable $e) {
        }

        return 0;
    }

    public function getAuthenticatedAdmin(): ?array
    {
        $user = $this->getUserEntity();
        if (!$user) {
            $this->jsonUnauthorized('Authentication required');
            return null;
        }

        $this->requireAnyRole(['admin'], $user);

        try {
            $db = $this->pdo->getPdo();
            if ($db instanceof \PDO) {
                $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
        } catch (\Throwable $e) {
            $this->json('Database connection error', 500);
            return null;
        }

        return [
            'user' => $user,
            'db'   => $db,
        ];
    }

    protected function validateToken()
    {
        if (isset($this->token) && $this->jwt->isValid($this->token) && !$this->jwt->isExpired($this->token) && $this->jwt->check($this->token, SECRET)) {
            return $this->jwt->getPayload($this->token);
        }
        return null;
    }

    protected function json($data, $statusCode = 200)
    {
        header_remove();
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        http_response_code($statusCode);

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur JSON : ' . json_last_error_msg()]);
        } else {
            echo $json;
        }
        exit;
    }

    public function view(string $path, ?array $params = null): void
    {
        $this->render($path, 'base.php', $params);
    }

    public function admin(string $path, ?array $params = null): void
    {
        $this->render($path, 'admin.php', $params);
    }

    public function generic(string $path, ?array $params = null): void
    {
        $this->render($path, 'generic.php', $params);
    }

    public function agency(string $path, ?array $params = null): void
    {
        $this->render($path, 'agency.php', $params);
    }

    public function followView(string $path, ?array $params = null): void
    {
        $this->render($path, 'follow.php', $params);
    }

    public function errors(string $path, ?array $params = null): void
    {
        $this->render($path, 'errors.php', $params);
    }

    public function ViewChat(string $path, ?array $params = null): void
    {
        $this->render($path, 'ViewChat.php', $params);
    }

    public function auth(string $path, ?array $params = null): void
    {
        $this->render($path, 'auth.php', $params);
    }
    public function shop(string $path, ?array $params = null): void
    {
        $this->render($path, 'shop.php', $params);
    }
    public function connectToDatabase()
    {
        static $conn = null;

        if ($conn !== null) {
            return $conn;
        }

        $hostname = DB_HOST;
        $username = DB_USER;
        $password = DB_PWD;
        $dbname   = DB_NAME;

        $conn = mysqli_connect($hostname, $username, $password, $dbname);

        if (!$conn) {
            echo json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]);
            return null;
        }

        return $conn;
    }

    public function getFollowersCount($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return 0;
        }

        $conn = $this->connectToDatabase();
        if ($conn === null) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as count FROM subscriptions WHERE following_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        return (int)$count;
    }

    public function getFollowingCount($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return 0;
        }

        $conn = $this->connectToDatabase();
        if ($conn === null) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as count FROM subscriptions WHERE follower_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        return (int)$count;
    }

    public static function handleImages($files, $directory, $prefix = 'softadastra')
    {
        if (!isset($files['tmp_name']) || !is_array($files['tmp_name']) || empty(array_filter($files['tmp_name']))) {
            throw new Exception("You haven't selected any images to upload.");
        }

        if (count($files['tmp_name']) > 20) {
            throw new Exception("You can only upload up to 20 images.");
        }

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new Exception("Unable to create upload directory.");
            }
        }

        $uploadedImages = [];
        $errors = [];

        foreach ($files['tmp_name'] as $key => $tmp_name) {
            $fileName = $files['name'][$key] ?? 'Unknown file';

            try {
                if (empty($tmp_name) || $files['error'][$key] === UPLOAD_ERR_NO_FILE) {
                    throw new Exception("No file selected.");
                }

                if ($files['error'][$key] !== UPLOAD_ERR_OK) {
                    throw new Exception("Upload error for file: $fileName");
                }

                $file = [
                    'name' => $fileName,
                    'type' => $files['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];

                $uploadedImage = PhotoHandler::photo($file, $prefix, $directory);
                $uploadedImages[] = $uploadedImage;
            } catch (Exception $e) {
                $message = $e->getMessage();
                $decoded = json_decode($message, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['message'])) {
                    $errors[] = "File '$fileName': " . $decoded['message'];
                } else {
                    $errors[] = "File '$fileName': " . $message;
                }

                continue;
            }
        }

        if (!empty($errors)) {
            foreach ($uploadedImages as $image) {
                @unlink($directory . '/' . $image);
            }

            throw new Exception(implode("\n", $errors));
        }

        return $uploadedImages;
    }

    public static function normalizeFile(array $files): array
    {
        $normalized = [];

        if (is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['name'][$i] && is_uploaded_file($files['tmp_name'][$i])) {
                    $normalized[] = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                }
            }
        } else {
            if ($files['name']) {
                $normalized[] = $files;
            }
        }

        return $normalized;
    }
}
