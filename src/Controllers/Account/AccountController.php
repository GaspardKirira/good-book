<?php

namespace Softadastra\Controllers\Account;

use Exception;
use Softadastra\Controllers\Controller;
use Domain\Users\UserRepository;
use Domain\Seller\SellerProfilesRepository;
use Domain\Seller\SellerVerificationRepository;
use Domain\Shops\SavedShopsRepository;
use PDO;

class AccountController extends Controller
{
    private $path = 'users.account.';
    private $errors = 'errors.';

    private UserRepository $users;
    private PDO $db;
    private SellerProfilesRepository $sellerProfiles;
    private SellerVerificationRepository $sellerVerification;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserRepository();
        $this->db   = $this->users->getDb();

        $this->sellerProfiles     = new SellerProfilesRepository($this->db);
        $this->sellerVerification = new SellerVerificationRepository($this->db);
    }

    public function legacyAccount()
    {
        header('Location: /account', true, 301);
        exit;
    }

    public function account()
    {
        try {
            $userEntity = $this->getUserEntity();
            $uid = (int)$userEntity->getId();

            $isSellerVerified    = $this->sellerProfiles->isVerified($uid);
            $hasOpenVerification = $this->sellerVerification->hasOpenRequest($uid);

            return $this->view($this->path . 'account', compact(
                'userEntity',
                'isSellerVerified',
                'hasOpenVerification'
            ));
        } catch (Exception $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function sendOptions()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'send-options', compact('userEntity'));
    }

    public function location()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'location', compact('userEntity'));
    }

    public function editProfile()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'edit-profile', compact('userEntity'));
    }

    public function password()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'update-password', compact('userEntity'));
    }

    public function security()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'security', compact('userEntity'));
    }

    public function privacy()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'privacy', compact('userEntity'));
    }

    public function notifications()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'notify', compact('userEntity'));
    }

    public function notificationSys()
    {
        $userEntity = $this->getUserEntity();
        return $this->view($this->path . 'notifications-systeme', compact('userEntity'));
    }

    public function startVerification()
    {
        try {
            $user = $this->getUserEntity();
            $uid  = (int)$user->getId();

            $users = new UserRepository();
            // $pdo   = $users->getDb();
            // $repo  = new SavedShopsRepository($pdo);

            // $limit  = 20;
            // $offset = 0;
            // $shops  = $repo->listByUser($uid, $offset, $limit);
            // $total  = $repo->countByUser($uid);

            return $this->view('users.account.seller.start-verification', compact('user'));
        } catch (\Throwable $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }

    public function verificationStatus()
    {
        try {
            $userEntity = $this->getUserEntity();
            return $this->view('users.account.seller.verification-status', compact('userEntity'));
        } catch (\Throwable $e) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => $e->getMessage()]);
        }
    }
}
