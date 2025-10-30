<?php

namespace Domain\Location;

use Domain\Model\BaseRepository;
use PDO;
use PDOException;

class ShopLocationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('shop_locations');
    }

    protected function map(array $data): ShopLocation
    {
        return new ShopLocation(
            (int)$data['user_id'],
            $data['address'],
            (float)$data['latitude'],
            (float)$data['longitude'],
            (bool)$data['is_public'],
            new \DateTime($data['created_at']),
            new \DateTime($data['updated_at'])
        );
    }

    public function findAllPublicWithDetails(): array
    {
        $sql = "
        SELECT 
        sl.user_id,
        sl.address,
        sl.latitude,
        sl.longitude,
        sl.is_public,
        sl.created_at,
        sl.updated_at,
        
        u.username,
        u.photo,
        
        cty.name AS city_name,
        ctry.name AS country_name,
        ctry.image_url AS country_flag,

        -- Nombre de produits actifs par vendeur
        COUNT(DISTINCT p.id) AS product_count,

        -- Nombre de followers (abonnés) du vendeur
        COUNT(DISTINCT s.follower_id) AS follower_count

    FROM shop_locations AS sl
    JOIN users AS u ON u.id = sl.user_id

    -- Localisation
    LEFT JOIN user_location AS ul ON ul.user_id = sl.user_id
    LEFT JOIN cities AS cty ON cty.id = ul.city_id
    LEFT JOIN countries AS ctry ON ctry.id = ul.country_id

    -- Produits actifs
    LEFT JOIN products AS p ON p.user_id = sl.user_id AND p.status = 'active'

    -- Abonnés (followers)
    LEFT JOIN subscriptions AS s ON s.following_id = sl.user_id

    WHERE sl.is_public = 1

    GROUP BY sl.user_id, sl.address, sl.latitude, sl.longitude, sl.is_public, sl.created_at, sl.updated_at,
            u.username, u.photo, cty.name, ctry.name, ctry.image_url

    ORDER BY sl.created_at ASC;
    ";


        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function findByUserId(int $userId): ?ShopLocation
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        return $this->fetchOne($sql, ['user_id' => $userId]);
    }

    public function save(ShopLocation $location): bool
    {
        $existing = $this->findByUserId($location->getUserId());

        if ($existing === null) {
            // INSERT
            $sql = "INSERT INTO {$this->table} (user_id, address, latitude, longitude, is_public, created_at, updated_at)
                    VALUES (:user_id, :address, :latitude, :longitude, :is_public, :created_at, :updated_at)";
            $params = [
                'user_id' => $location->getUserId(),
                'address' => $location->getAddress(),
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
                'is_public' => $location->isPublic() ? 1 : 0,
                'created_at' => $location->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $location->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
            return $this->executeQuery($sql, $params);
        } else {
            // UPDATE
            $sql = "UPDATE {$this->table} SET
                    address = :address,
                    latitude = :latitude,
                    longitude = :longitude,
                    is_public = :is_public,
                    updated_at = :updated_at
                    WHERE user_id = :user_id";
            $params = [
                'address' => $location->getAddress(),
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
                'is_public' => $location->isPublic() ? 1 : 0,
                'updated_at' => $location->getUpdatedAt()->format('Y-m-d H:i:s'),
                'user_id' => $location->getUserId(),
            ];
            return $this->executeQuery($sql, $params);
        }
    }

    public function delete(int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        return $this->executeQuery($sql, ['user_id' => $userId]);
    }

    public function getAllPublic(): iterable
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_public = 1 ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $this->map($row);
        }
    }
}
