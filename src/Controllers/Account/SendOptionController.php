<?php

namespace Softadastra\Controllers\Account;

use Softadastra\Controllers\Controller;
use Domain\SendOptions\SendOptionRepository;
use Domain\SendOptions\SendOption;

class SendOptionController extends Controller
{
    private SendOptionRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new SendOptionRepository();
        header('Content-Type: application/json; charset=utf-8');
    }

    public function index()
    {
        $user = $this->getUserEntityOr401();
        $items = $this->repo->findByUserId($user->getId());

        $data = array_map(fn($s) => [
            'user_id' => $s->getUserId(),
            'vendor_shipping_option_id' => $s->getVendorShippingOption(),
            'active' => (bool)$s->getIsActive()
        ], $items);

        return $this->json(['data' => $data], 200);
    }

    public function show(int $id)
    {
        $user = $this->getUserEntityOr401();
        $item = $this->repo->findByUserAndOption($user->getId(), $id);
        if (!$item) {
            return $this->jsonError(404, 'not_found', 'Send option not found');
        }
        return $this->json(['data' => [
            'user_id' => $item->getUserId(),
            'vendor_shipping_option_id' => $item->getVendorShippingOption(),
            'active' => (bool)$item->getIsActive()
        ]], 200);
    }

    public function upsert(int $id)
    {
        $user = $this->getUserEntityOr401();
        if (!$this->isJson()) {
            return $this->jsonError(415, 'unsupported_media_type', 'Use application/json');
        }

        $active = true;

        $existing = $this->repo->findByUserAndOption($user->getId(), $id);
        if ($existing) {
            $existing->setVendorShippingOption($id);
            $existing->setIsActive(true);
            $this->repo->update($existing);

            return $this->json(['data' => [
                'user_id' => $user->getId(),
                'vendor_shipping_option_id' => $id,
                'active' => true
            ]], 200);
        }

        $entity = new SendOption($user->getId(), $id, 1); // 1 forcé
        $this->repo->save($entity);

        header('Location: /api/v1/me/send-options/' . $id, true, 201);
        return $this->json(['data' => [
            'user_id' => $user->getId(),
            'vendor_shipping_option_id' => $id,
            'active' => true
        ]], 201);
    }

    public function destroy(int $id)
    {
        $user = $this->getUserEntityOr401();
        $existing = $this->repo->findByUserAndOption($user->getId(), $id);
        if (!$existing) {
            // DELETE is idempotent — respond 204 even if nothing existed
            http_response_code(204);
            return;
        }
        $this->repo->delete($existing);
        http_response_code(204);
    }

    // ---- helpers ----
    private function getUserEntityOr401()
    {
        $u = $this->getUserEntity();
        if (!$u) {
            $this->jsonUnauthorized('Authentication required');
        }
        return $u;
    }

    protected function isJson(): bool
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        return stripos($ct, 'application/json') !== false;
    }

    protected function jsonError(int $status, string $code, string $message)
    {
        http_response_code($status);
        echo json_encode(['error' => ['code' => $code, 'message' => $message]]);
        exit;
    }
}
