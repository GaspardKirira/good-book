<?php

use Softadastra\Exception\NotFoundException;
use Softadastra\Router\Router;

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents('request.log', $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/vendor/autoload.php');

$envFile = '.env';
if (!empty($_SERVER['APP_ENV'])) {
    $candidate = ".env.{$_SERVER['APP_ENV']}";
    if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . $candidate)) {
        $envFile = $candidate;
    }
}
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, $envFile);
$dotenv->load();

define('BASE_PATH', __DIR__);

define('VIEWS', __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);

use Cloudinary\Configuration\Configuration;

Configuration::instance([
    'cloud' => [
        'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'] ?? '',
        'api_key'    => $_ENV['CLOUDINARY_API_KEY'] ?? '',
        'api_secret' => $_ENV['CLOUDINARY_API_SECRET'] ?? '',
    ],
    'url' => ['secure' => true],
]);

define('CLOUDINARY_CLOUD_NAME', $_ENV['CLOUDINARY_CLOUD_NAME'] ?? '');
define('CLOUDINARY_API_KEY',    $_ENV['CLOUDINARY_API_KEY'] ?? '');
define('CLOUDINARY_API_SECRET', $_ENV['CLOUDINARY_API_SECRET'] ?? '');
define('CLOUDINARY_FOLDER',     $_ENV['CLOUDINARY_FOLDER'] ?? 'good-book/users');

define("BASE_URL", $_ENV['BASE_URL'] ?? "");
define("ADMIN_URL", $_ENV['ADMIN_URL'] ?? (BASE_URL . "admin" . "/"));
define('CSS_PATH', $_ENV['CSS_PATH'] ?? '/public/assets/css/');
define('JS_PATH', $_ENV['JS_PATH'] ?? '/public/assets/js/');
define('CSS_ADMIN', $_ENV['CSS_ADMIN'] ?? '/public/assets/admin/css/');
define('JS_ADMIN', $_ENV['JS_ADMIN'] ?? '/public/assets/admin/js/');
define('FAVICON_PATH', $_ENV['FAVICON_PATH'] ?? '/public/assets/favicon/');
define('IMAGE_PATH', $_ENV['IMAGE_PATH'] ?? '/public/images/');
define('ASSETS_VERSION', $_ENV['ASSETS_VERSION'] ?? '1');
define('SECRET', $_ENV['SECRET'] ?? 'fallback_secret');

define('APP_ENV', $_ENV['APP_ENV'] ?? 'prod');
define('API_LOCAL', $_ENV['API_LOCAL'] ?? 'http://127.0.0.1:3001');
define('API_PROD',  $_ENV['API_PROD']  ?? 'https://api.good-book.com');

/** DB */
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_HOST', $_ENV['DB_HOST'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PWD',  $_ENV['DB_PWD'] ?? '');

if (php_sapi_name() !== 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $file = __DIR__ . $path;

    if (file_exists($file) && is_file($file)) {
        return false;
    }
}

function logger($data, $exit = true)
{
    if (empty($data)) {
        echo "<p style='color: red; font-family: Arial, sans-serif; font-size: 14px;'>Aucune donnée à afficher.</p>";
        return;
    }

    echo '<div style="background-color: #f4f4f4; border: 1px solid #ccc; padding: 15px; font-family: monospace; max-width: 100%; margin: 20px 0;">';
    echo '<pre style="background-color: #f4f4f4; border: none; padding: 10px; font-family: monospace; color: #333; font-size: 14px;">';
    print_r($data);
    echo '</pre>';
    echo '</div>';

    echo '<div style="text-align: center; padding: 10px; font-family: Arial, sans-serif; font-size: 12px; color: #888; border-top: 1px solid #ccc;">';
    echo 'softadastra debug | &copy; ' . date('Y') . ' All rights reserved.';
    echo '</div>';

    if ($exit) {
        exit;
    }
}

function profile_url(string $username): string
{
    return '/@' . rawurlencode($username);
}

function timeDebug(float $end, float $debut)
{
    $time = round(1000 * ($end - $debut), 3);
    echo '<pre>';
    print_r($time . "ms");
    echo '</pre>';
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$router = new Router($url);

$router->get('/', 'Softadastra\Controllers\\Product\\ProductController@home');

// Dashboard 
$router->get('/dashboard',     'Softadastra\Controllers\\UserController@dashboard');
$router->get('/user/dashboard', 'Softadastra\Controllers\\UserController@dashboard');
$router->get('/get-user', 'Softadastra\Controllers\\UserController@getUserJson');
$router->get('/api/get-user', 'Softadastra\Controllers\\UserController@getUserJson');
$router->get('/api/profile/:slug', 'Softadastra\Controllers\\UserController@getProfile');
// Canonique (privé)
$router->get('/account', 'Softadastra\Controllers\\Account\\AccountController@account');
$router->get('/account/edit-profile', 'Softadastra\Controllers\\Account\\AccountController@editProfile');
$router->post('/account/edit-profile', 'Softadastra\Controllers\\Account\\ProfileController@postEditProfile');
// Cover and profile photo
$router->post('/user/update-cover', 'Softadastra\Controllers\\Account\\ProfileController@updatePhoto');
$router->post('/user/update-photo', 'Softadastra\Controllers\\Account\\ProfileController@updatePhoto');
// Publique profile 
$router->get('/@:slug', 'Softadastra\Controllers\\Account\\ProfileController@publicProfile');
$router->get('/profile/:slug', 'Softadastra\Controllers\\Account\\ProfileController@myProfile');
$router->get('/account/security', 'Softadastra\Controllers\\Account\\AccountController@security');
$router->get('/account/privacy', 'Softadastra\Controllers\\Account\\AccountController@privacy');
// Location
$router->get('/account/location', 'Softadastra\Controllers\\Account\\AccountController@location');
$router->get('/api/get-location', 'Softadastra\Controllers\\Account\\LocationController@getLocationAPI');
$router->post('/api/create-location', 'Softadastra\Controllers\\Account\\LocationController@createLocation');
$router->post('/api/update-location', 'Softadastra\Controllers\\Account\\LocationController@updateLocation');
// Countries and cities
$router->get('/api/get-countries', 'Softadastra\Controllers\\Account\\CountryController@countries');
$router->get('/api/get-cities/:id', 'Softadastra\Controllers\\Account\\CityController@cities');
// Send options
$router->get('/account/send-options', 'Softadastra\Controllers\\Account\\AccountController@sendOptions');
// Send-options
$router->get('/api/v1/me/send-options', 'Softadastra\Controllers\\Account\\SendOptionController@index');
$router->get('/api/v1/me/send-options/:id', 'Softadastra\Controllers\\Account\\SendOptionController@show');
$router->put('/api/v1/me/send-options/:id', 'Softadastra\Controllers\\Account\\SendOptionController@upsert');
$router->delete('/api/v1/me/send-options/:id', 'Softadastra\Controllers\\Account\\SendOptionController@destroy');
// Password
$router->get('/account/update-password', 'Softadastra\Controllers\\Account\\AccountController@password');
$router->post('/account/update-password', 'Softadastra\Controllers\\Account\\ProfileController@updatePassword');
// Payments
$router->get('/account/payments', 'Softadastra\Controllers\\Payment\\PaymentMethodController@page');
$router->get('/api/payments', 'Softadastra\Controllers\\Payment\\PaymentMethodController@listGet');
$router->post('/api/payments', 'Softadastra\Controllers\\Payment\\PaymentMethodController@createOrUpdatePOST');
$router->post('/api/payments/:id/default', 'Softadastra\Controllers\\Payment\\PaymentMethodController@setDefaultPOST');
$router->post('/api/payments/:id', 'Softadastra\Controllers\\Payment\\PaymentMethodController@deleteDELETE');
// Verification seller
$router->get('/explore',             'Softadastra\\Controllers\\Explore\\ExploreController@verified');
$router->get('/explore/verified',    'Softadastra\\Controllers\\Explore\\ExploreController@verified');
// (tes routes existantes)
$router->get('/seller/verification/start',          'Softadastra\\Controllers\\Account\\AccountController@startVerification');
$router->get('/seller/verification/status',         'Softadastra\\Controllers\\Account\\AccountController@verificationStatus');
$router->get('/api/seller/verification/me',         'Softadastra\\Controllers\\Account\\VerificationController@me');
$router->post('/api/seller/verification/request',   'Softadastra\\Controllers\\Account\\VerificationController@request');
$router->post('/api/seller/verification/request/:id', 'Softadastra\\Controllers\\Account\\VerificationController@update');
$router->post('/api/seller/verification/preview',   'Softadastra\\Controllers\\Account\\VerificationController@preview');
$router->post('/api/seller/verification/withdraw',  'Softadastra\\Controllers\\Account\\VerificationController@withdraw');
$router->post('/api/seller/verification/reapply',   'Softadastra\\Controllers\\Account\\VerificationController@reapply');
$router->get('/api/explore/verified',               'Softadastra\\Controllers\\Explore\\VerifiedController@list');

// Saved sellers (business vérifiés)
$router->get('/account/sellers/saved', 'Softadastra\\Controllers\\Account\\SavedSellersController@savedPage');
$router->get('/api/me/saved-sellers',       'Softadastra\\Controllers\\Account\\SavedSellersController@list');
$router->get('/api/me/saved-sellers/count', 'Softadastra\\Controllers\\Account\\SavedSellersController@count');
$router->post('/api/sellers/:id/save',      'Softadastra\\Controllers\\Account\\SavedSellersController@save');
$router->delete('/api/sellers/:id/save',    'Softadastra\\Controllers\\Account\\SavedSellersController@unsave');
$router->get('/seller/center', 'Softadastra\\Controllers\\Account\\CenterController@index');



try {
    $router->run();
} catch (NotFoundException $e) {
    return $e->error404();
}
