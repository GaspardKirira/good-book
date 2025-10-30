<?php

namespace Domain\SendOptions;

use Domain\Model\BaseRepository;
use Domain\Model\Table;
use PDO;

class UserShippingPreferenceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Table::USER_SHIPPING_PREFERENCES);
    }

    protected function map(array $row): SendOption
    {
        $e = new SendOption(
            (int)$row['user_id'],
            (int)$row['vendor_shipping_option_id'],
            (int)($row['active'] ?? 0)
        );
        if (isset($row['id'])) $e->setId((int)$row['id']);
        return $e;
    }

    public function upsert(int $userId, int $optionId, bool $active = true): bool
    {
        $sql = "INSERT INTO {$this->table} (user_id, vendor_shipping_option_id, active)
                VALUES (:uid, :oid, :act)
                ON DUPLICATE KEY UPDATE active = VALUES(active)";
        return $this->executeQuery($sql, [
            'uid' => $userId,
            'oid' => $optionId,
            'act' => $active ? 1 : 0,
        ]);
    }

    public function setActive(int $userId, int $optionId, bool $active, bool $createIfMissing = true): bool
    {
        $sql = "UPDATE {$this->table}
                   SET active = :act
                 WHERE user_id = :uid AND vendor_shipping_option_id = :oid";
        $ok  = $this->executeQuery($sql, ['act' => $active ? 1 : 0, 'uid' => $userId, 'oid' => $optionId]);
        if (!$ok && $createIfMissing) {
            return $this->upsert($userId, $optionId, $active);
        }
        return $ok;
    }

    public function deleteOne(int $userId, int $optionId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :uid AND vendor_shipping_option_id = :oid";
        return $this->executeQuery($sql, ['uid' => $userId, 'oid' => $optionId]);
    }

    public function deleteAllForUser(int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :uid";
        return $this->executeQuery($sql, ['uid' => $userId]);
    }

    public function find(int $userId, int $optionId): ?SendOption
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND vendor_shipping_option_id = :oid LIMIT 1";
        return $this->fetchOne($sql, ['uid' => $userId, 'oid' => $optionId]) ?: null;
    }

    public function listByIds(array $ids): array
    {
        if (empty($ids)) return [];
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE id IN ($in) ORDER BY name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_map('intval', $ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function listActiveAgencyIdsByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT vendor_shipping_option_id
                                     FROM {$this->table}
                                     WHERE user_id = :uid AND active = 1");
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => (int)$r['vendor_shipping_option_id'], $rows);
    }

    public function listActiveWithVendorDetails(int $userId): array
    {
        $sql = "SELECT usp.user_id, usp.vendor_shipping_option_id, usp.active,
                       vso.id AS agency_id, vso.name, vso.logo, vso.address, vso.description,
                       vso.country, vso.city
                FROM {$this->table} usp
                JOIN vendor_shipping_options vso ON vso.id = usp.vendor_shipping_option_id
                WHERE usp.user_id = :uid AND usp.active = 1
                ORDER BY vso.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function dedupe(): int
    {
        $sql = "DELETE usp1
                FROM {$this->table} usp1
                JOIN {$this->table} usp2
                  ON usp1.user_id = usp2.user_id
                 AND usp1.vendor_shipping_option_id = usp2.vendor_shipping_option_id
                 AND usp1.id > usp2.id";
        $stmt = $this->pdo->query($sql);
        return (int)$stmt->rowCount();
    }
}
