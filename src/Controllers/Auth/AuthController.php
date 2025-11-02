<?php

namespace Softadastra\Controllers\Auth;

use Domain\Users\UserHelper;
use Domain\Users\UserRepository;
use Domain\Users\UserService;
use Exception;
use Google\Client;
use Softadastra\Application\Http\RedirectionHelper;
use Softadastra\Application\Utils\Adastra;
use Softadastra\Application\Utils\FlashMessage;
use Softadastra\Controllers\Controller;
use Softadastra\Model\EmailService;
use Softadastra\Model\GetUser;

class AuthController extends Controller
{
    private $path = 'auth.';
    private $errors = 'errors.';

    public function register($ref = null)
    {
        try {
            Adastra::getCookie();

            // 🔥 récupère la référence soit dans l'URL soit dans l'argument
            if (isset($_GET['ref'])) {
                $_SESSION['referral_username'] = htmlspecialchars($_GET['ref']);
            } elseif ($ref) {
                $_SESSION['referral_username'] = htmlspecialchars($ref);
            }

            $ref = $_SESSION['referral_username'] ?? null;

            return $this->view($this->path . 'register', compact('ref'));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function postRegister()
    {
        try {
            if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                return $this->json(["success" => false, "error" => "Invalid CSRF token."], 400);
            }

            if (empty($_POST)) {
                return $this->json(["success" => false, "error" => "Please fill all fields."], 400);
            }

            $userRepository = new UserRepository($this->pdo);
            $userService    = new UserService($userRepository);

            $post         = $_POST;
            $fullname     = $post['fullname'] ?? '';
            $email        = $post['email'] ?? '';
            $password     = $post['password'] ?? '';
            // 🔧 FIX: lit phone_number (fallback sur phone pour compat)
            $phone_number = $post['phone_number'] ?? ($post['phone'] ?? '');

            // (optionnel) normalisation serveur en E.164
            $phone_number = $this->normalizeE164($phone_number);

            // Appel service
            $result = $userService->register($fullname, $email, $password, $phone_number);

            // $result est supposé être une structure que handleSuccess/handleError renvoie.
            // Si handleSuccess/handleError retournent déjà la réponse JSON, adapte selon ton framework.
            // Ici on standardise:
            if (isset($result['success']) && $result['success'] === true) {
                return $this->json($result, $result['status'] ?? 201);
            }
            return $this->json($result, $result['status'] ?? 422);
        } catch (Exception $e) {
            return $this->json(["success" => false, "error" => "Server error."], 500);
        }
    }

    /** Normalise grossièrement vers E.164 pour UG/CD */
    private function normalizeE164(string $raw): string
    {
        $v = trim($raw);
        $v = preg_replace('/[^\d+]/', '', $v ?? '');

        if ($v === '') return $v;

        // Déjà +256 / +243 → nettoie
        if (strpos($v, '+256') === 0) {
            $d = preg_replace('/\D/', '', substr($v, 1));
            return '+256' . substr($d, 3, 9);
        }
        if (strpos($v, '+243') === 0) {
            $d = preg_replace('/\D/', '', substr($v, 1));
            return '+243' . substr($d, 3, 9);
        }

        // Uganda variantes
        if (strpos($v, '256') === 0) {
            $d = preg_replace('/\D/', '', $v);
            return '+256' . substr($d, 3, 9);
        }
        if (strpos($v, '07') === 0) {
            $d = preg_replace('/\D/', '', $v);
            return '+256' . substr($d, 1, 9);
        }
        if (preg_match('/^7\d{8,}$/', $v)) {
            $d = preg_replace('/\D/', '', $v);
            return '+256' . substr($d, 0, 9);
        }

        // DRC variantes
        if (strpos($v, '243') === 0) {
            $d = preg_replace('/\D/', '', $v);
            return '+243' . substr($d, 3, 9);
        }
        if (preg_match('/^0[89]\d+$/', $v)) {
            $d = preg_replace('/\D/', '', $v);
            return '+243' . substr($d, 1, 9);
        }
        if (preg_match('/^[89]\d+$/', $v)) {
            $d = preg_replace('/\D/', '', $v);
            return '+243' . substr($d, 0, 9);
        }

        // fallback: renvoie tel quel (le validator gèrera)
        return $v;
    }


    private function getGoogleClient()
    {
        $client = new Client();
        $client->setClientId('');
        $client->setClientSecret('');
        $client->setRedirectUri('');
        $client->addScope('email');
        $client->addScope('profile');

        return $client;
    }

    public function getGoogleLoginUrl()
    {
        try {
            $client = $this->getGoogleClient();
            $authUrl = $client->createAuthUrl();
            echo json_encode(['url' => $authUrl]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['error' => 'Impossible to generate login URL']);
            exit;
        }
    }

    public function login($ref = null)
    {
        try {
            Adastra::getCookie();
            if (isset($_GET['ref'])) {
                $_SESSION['referral_username'] = htmlspecialchars($_GET['ref']);
            } elseif ($ref) {
                $_SESSION['referral_username'] = htmlspecialchars($ref);
            }

            $ref = $_SESSION['referral_username'] ?? null;

            $client = $this->getGoogleClient();
            return $this->view($this->path . 'login', compact('client'));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function loginEmail($ref = null)
    {
        try {
            Adastra::getCookie();
            if (isset($_GET['ref'])) {
                $_SESSION['referral_username'] = htmlspecialchars($_GET['ref']);
            } elseif ($ref) {
                $_SESSION['referral_username'] = htmlspecialchars($ref);
            }

            $ref = $_SESSION['referral_username'] ?? null;

            $client = $this->getGoogleClient();
            return $this->view($this->path . 'login.email', compact('client'));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function redirectToGoogle()
    {
        try {
            $client = $this->getGoogleClient();
            $authUrl = $client->createAuthUrl();
            return RedirectionHelper::redirect($authUrl);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function google($code = null, $scope = null, $authuser = null, $prompt = null)
    {
        try {

            if (!$code) {
                return $this->errors($this->errors . 'errors', ['errorMessage' => "Erreur : Missing authentication code."]);
            }

            $client = $this->getGoogleClient();

            $token = $client->fetchAccessTokenWithAuthCode($code);


            if (isset($token['error'])) {
                return $this->errors($this->errors . 'errors', ['errorMessage' => 'Error retrieving the token : ' . $token['error']]);
            }

            $client->setAccessToken($token);
            $oauth = new \Google\Service\Oauth2($client);
            $user = $oauth->userinfo->get();

            $userRepository = new UserRepository($this->pdo);
            $userService = new UserService($userRepository);
            $userService->google($user);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function finalizeRegistration()
    {
        if (!isset($_SESSION['user_registration'])) {
            return RedirectionHelper::redirect("login");
        }
        return $this->view($this->path . 'finalize-register');
    }

    public function finalizeRegistrationPost()
    {
        try {
            $userRepository = new UserRepository();
            $userService = new UserService($userRepository);
            $userService->finalizeRegistrationPOST($_POST);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function postLogin()
    {
        try {
            Adastra::getCookie();

            if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['errors_user_register'] = "Invalid CSRF token.";
                echo "Invalid CSRF token.";
                exit;
                return RedirectionHelper::redirect("register");
            }

            $userRepository = new UserRepository($this->pdo);
            $userService = new UserService($userRepository);
            if (empty($_POST)) {
                RedirectionHelper::redirect('login');
            }
            $userService->login($_POST['email'], $_POST['password']);
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    private function clearAuthCookies(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

        // Purge session + cookie PHPSESSID
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            @setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path']     ?? '/',
                'domain'   => $p['domain']   ?? '',
                'secure'   => !empty($p['secure']),
                'httponly' => !empty($p['httponly']),
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        @session_destroy();
        @session_write_close();

        // Efface le cookie "token" avec les mêmes attributs que lors de l’émission
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isLocal = (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host);

        $opts = [
            'expires'  => time() - 42000,
            'path'     => '/',
            'secure'   => $isLocal ? false : true,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        if (!$isLocal) {
            $opts['domain'] = '.softadastra.com';
        }

        // supprime "token" (et un éventuel alias "phpjwt")
        @setcookie('token',  '', $opts);
        @setcookie('phpjwt', '', $opts);
    }

    public function logout()
    {
        try {
            // ✅ Récupération du payload via GetUser (si tu veux logger l’ID/analytics)
            $payload = null;
            try {
                $payload = (new GetUser())->validateToken(); // retourne array|null
            } catch (\Throwable $e) {
                // ignore: le logout ne doit pas dépendre de ça
            }

            // Nettoyage côté serveur + cookies
            $this->clearAuthCookies();

            // Empêcher toute mise en cache
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            FlashMessage::add('success', 'You have been successfully logged out.');
            return RedirectionHelper::redirect('login');
        } catch (\Throwable $e) {
            return $this->errors($this->errors . 'errors', [
                'errorMessage' => $e->getMessage() . ' — Tip: supprime manuellement les cookies dans l’onglet Application si besoin.'
            ]);
        }
    }

    public function me()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $payload = (new GetUser())->validateToken();
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'user' => null]);
            return;
        }
        echo json_encode(['ok' => true, 'user' => ['id' => $payload['id'] ?? null]]);
    }

    public function forgotPassword()
    {
        return $this->view($this->path . 'forgot_password');
    }

    public function postForgotPassword()
    {
        try {
            // Vérification du token CSRF
            if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['message'] = "Token CSRF invalide.";
                return RedirectionHelper::redirect("auth/forgot-password");
            }

            $email = trim($_POST['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['message'] = "Please enter a valid email address.";
                return RedirectionHelper::redirect("auth/forgot-password");
            }

            $userRepository = new UserRepository();
            $user = $userRepository->findByEmail($email);
            if (!$user) {
                $_SESSION['message'] = "No account found with this email address.";
                return RedirectionHelper::redirect("auth/forgot-password");
            }

            if (!$user->getPassword()) {
                return RedirectionHelper::redirect("login");
            }

            $validity = 60 * 30;
            $resetToken = UserHelper::token($user, $validity);
            $userRepository->updateResetToken($user, $resetToken);

            $resetLink = "https://softadastra.com/auth/reset-password?token=" . $resetToken;

            $_SESSION['message'] = "A verification code has been sent to your email address.";
        } catch (Exception $e) {
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
        }

        return RedirectionHelper::redirect("auth/forgot-password");
    }

    public function resetPassword($token = null, $scope = null, $authuser = null, $prompt = null)
    {
        if (is_null($token)) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => 'Invalid or missing token.']);
        }
        return $this->view($this->path . 'reset_password', compact('token'));
    }

    public function postResetPassword()
    {
        try {
            $userRepository = new UserRepository();
            $userService = new UserService($userRepository);
            $userService->resetPassword($_POST);
        } catch (Exception $e) {
            $_SESSION['message'] = "An error occurred: " . $e->getMessage();
            RedirectionHelper::redirect("auth/reset-password");
        }
    }

    public function pubFacebook()
    {
        try {
            return $this->errors($this->path . 'pub-facebook');
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function AuthSync()
    {
        try {
            return $this->errors($this->path . 'auth-sync');
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }
}
