<?php

namespace Domain\SendOptions;

use Domain\Model\BaseRepository;
use Domain\Model\Table;
use Exception;
use PDO;

class SendOptionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Table::USER_SHIPPING_PREFERENCES);
    }

    public function save(SendOption $entity): SendOption
    {
        $sql = "INSERT INTO {$this->table} (user_id, vendor_shipping_option_id, active)
            VALUES (:user_id, :vendor_shipping_option_id, 1)";

        $isSaved = $this->executeQueries($sql, [
            'user_id' => $entity->getUserId(),
            'vendor_shipping_option_id' => $entity->getVendorShippingOption(),
        ]);

        if ($isSaved) return $entity;
        throw new Exception('Failed to save SendOption');
    }

    public function update(SendOption $entity)
    {
        $sql = "UPDATE {$this->table}
            SET active = 1
            WHERE user_id = :user_id AND vendor_shipping_option_id = :vendor_shipping_option_id";

        $this->executeQueries($sql, [
            'user_id' => $entity->getUserId(),
            'vendor_shipping_option_id' => $entity->getVendorShippingOption(),
        ]);

        return true;
    }

    public function upsertForceActive(int $userId, int $optionId): bool
    {
        $sql = "INSERT INTO {$this->table} (user_id, vendor_shipping_option_id, active)
            VALUES (:uid, :oid, 1)
            ON DUPLICATE KEY UPDATE active = 1";
        return $this->executeQueries($sql, ['uid' => $userId, 'oid' => $optionId]);
    }



    public function findByUserAndOption($userId, $optionId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND vendor_shipping_option_id = :vendor_shipping_option_id LIMIT 1";
        $result = $this->executeQueries($sql, [
            'user_id' => $userId,
            'vendor_shipping_option_id' => $optionId
        ]);

        if (is_array($result)) {
            return new SendOption(
                $result['user_id'],
                $result['vendor_shipping_option_id'],
                $result['active'] ?? 0
            );
        }

        return null;
    }

    public function listActiveAgencyIdsByUser(int $userId): array
    {
        $sql = "SELECT vendor_shipping_option_id
        FROM {$this->table}
        WHERE user_id = :uid AND active = 1";
        $rows = $this->executeQueryArray($sql, ['uid' => $userId]);

        return array_map(fn($r) => (int)$r['vendor_shipping_option_id'], $rows ?: []);
    }

    public function delete(SendOption $entity)
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id AND vendor_shipping_option_id = :vendor_shipping_option_id";

        $this->executeQueries($sql, [
            'user_id' => $entity->getUserId(),
            'vendor_shipping_option_id' => $entity->getVendorShippingOption()
        ]);

        return true;
    }

    public function executeQueries($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if (strpos(strtoupper($sql), 'SELECT') === 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return $stmt->rowCount() > 0;
    }

    public function executeQueryArray($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if (strpos(strtoupper($sql), 'SELECT') === 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $stmt->rowCount() > 0;
    }

    public function findByUserId($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $result = $this->executeQueryArray($sql, ['user_id' => $userId]);
        if (is_array($result) && !empty($result)) {
            $sendOptions = [];
            foreach ($result as $row) {
                $sendOptions[] = new SendOption($row['user_id'], $row['vendor_shipping_option_id']);
            }

            return $sendOptions;
        }
        return [];
    }

    public function findByName(string $name): ?SendOption
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $entity = $this->fetchOne($sql, ['name' => $name]);
        return $entity ? $entity : null;
    }

    public function findById(int $id): ?SendOption
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $entity = $this->fetchOne($sql, ['id' => $id]);
        return $entity ? $entity : null;
    }

    protected function map(array $data): SendOption
    {
        $entity = new SendOption(
            $data['user_id'],
            $data['vendor_shipping_option_id']
        );
        $entity->setId($data['id']);
        return $entity;
    }
}
