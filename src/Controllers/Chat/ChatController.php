<?php

namespace Softadastra\Controllers\Chat;

use Domain\Users\UserRepository;
use Softadastra\Controllers\Controller;

class ChatController extends Controller
{
    private $path = 'chat.';
    private $errors = 'errors.';

      public function home()
    {
        $userEntity = $this->getUserEntity();
        $userRepository = new UserRepository();
        $users = iterator_to_array($userRepository->getUsers());
        $users = array_filter($users, fn($u) => $u->getId() !== $userEntity->getId());
        if (!$users) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => 'User not found']);
        }
        return $this->view($this->path . 'chat', compact('userEntity', 'users'));
    }


    public function chat(int $id)
    {
        $userEntity     = $this->getUserEntity();
        $userRepository = new UserRepository();
        $user           = $userRepository->findById($id);

        if (!$user) {
            return $this->errors($this->errors . 'errors', ['errorMessage' => 'User not found']);
        }

        $offer = null;
        if (!empty($_GET['offer'])) {
            // base64url safe
            $raw = strtr($_GET['offer'], '-_', '+/');
            $decoded = base64_decode($raw, true);
            if ($decoded !== false) {
                $candidate = json_decode($decoded, true);

                // Validation stricte
                $allowedKeys = ['product_id', 'title', 'price', 'image', 'size', 'color', 'quantity', 'currency'];
                if (is_array($candidate)) {
                    // on ne garde que les clés autorisées
                    $filtered = array_intersect_key($candidate, array_flip($allowedKeys));

                    // types simples
                    if (isset($filtered['product_id'])) $filtered['product_id'] = (int)$filtered['product_id'];
                    if (isset($filtered['price']))      $filtered['price']      = (float)$filtered['price'];
                    if (isset($filtered['quantity']))   $filtered['quantity']   = max(1, (int)$filtered['quantity']);

                    // titrage / image limité pour éviter abus
                    if (isset($filtered['title'])) $filtered['title'] = substr(trim((string)$filtered['title']), 0, 180);
                    if (isset($filtered['image'])) $filtered['image'] = substr(trim((string)$filtered['image']), 0, 200);

                    // petit contrôle minimum
                    if (!empty($filtered['product_id']) && !empty($filtered['title']) && isset($filtered['price'])) {
                        $offer = $filtered;
                    }
                }
            }
        }

        return $this->ViewChat($this->path . 'conversation', [
            'userEntity' => $userEntity,
            'user'       => $user,
            'offer'      => $offer,
        ]);
    }
}