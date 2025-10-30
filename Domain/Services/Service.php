<?php

namespace Domain\Services;

use App\Response\JsonResponse;
use Domain\Users\UserRepository;
use Exception;
use Softadastra\Application\Http\RedirectionHelper;
use Softadastra\Application\Image\PhotoHandler;
use Softadastra\Model\JWT;

abstract class Service
{
    /** @var JWT */
    private $jwt;

    /** @var string|null */
    private $token;

    public function __construct()
    {
        $this->jwt = new JWT();

        $cookieToken = $_COOKIE['token'] ?? $_COOKIE['jwt'] ?? null;

        $bearer = $this->getBearerToken();

        $this->token = $cookieToken ?: $bearer;
    }

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return (stripos($accept, 'application/json') !== false) || ($xhr === 'XMLHttpRequest');
    }

    protected function getBearerToken(): ?string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        $auth = $headers['Authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if ($auth && stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return null;
    }

    public function validateToken(?string $rawToken = null): ?array
    {
        try {
            $tok = $rawToken ?: $this->token;
            if (!$tok) {
                return null;
            }

            if (
                $this->jwt->isValid($tok) &&
                !$this->jwt->isExpired($tok) &&
                $this->jwt->check($tok, SECRET)
            ) {
                return $this->jwt->getPayload($tok);
            }

            return null;
        } catch (\Throwable $e) {
            error_log('validateToken error: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserEntity(bool $requireAuth = true)
    {
        $payload = $this->validateToken();
        if ($payload && isset($payload['id'])) {
            $userRepository = new UserRepository();
            return $userRepository->findById((int)$payload['id']);
        }

        if ($requireAuth) {
            if ($this->wantsJson()) {
                JsonResponse::unauthorized('You must be logged in.');
            } else {
                RedirectionHelper::redirect('/login');
            }
        }

        return null;
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
}
