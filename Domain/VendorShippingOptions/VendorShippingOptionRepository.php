<?php

namespace Domain\VendorShippingOptions;

use Domain\Model\BaseRepository;
use Domain\Model\Table;
use PDO;
use PDOException;

class VendorShippingOptionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Table::VENDOR_SHIPPING_OPTIONS);
    }

    public function getPdo()
    {
        return $this->pdo();
    }

    protected function executeQueryArray(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('executeQueryArray error: ' . $e->getMessage());
            return [];
        }
    }

    /** Map une ligne -> entité */
    protected function map(array $data): VendorShippingOption
    {
        return VendorShippingOption::fromArray($data);
    }

    public function save(VendorShippingOption $entity): VendorShippingOption
    {
        $sql = "INSERT INTO {$this->table}
       (logo, name, address, description, owner_user_id, phone, email, website,
        country, city, latitude, longitude, is_active, created_at, updated_at)
     VALUES
       (:logo, :name, :address, :description, :owner, :phone, :email, :website,
        :country, :city, :lat, :lng, :active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        $this->executeQuery($sql, [
            'logo'       => $entity->getImage(),
            'name'       => $entity->getName(),
            'address'    => $entity->getAddress(),
            'description' => $entity->getDescription(),
            'owner'      => $entity->getOwnerUserId(),
            'phone'      => $entity->getPhone(),
            'email'      => $entity->getEmail(),
            'website'    => $entity->getWebsite(),
            'country'    => $entity->getCountry(),   // déjà ISO-2 via setter
            'city'       => $entity->getCity(),
            'lat'        => $entity->getLatitude(),
            'lng'        => $entity->getLongitude(),
            'active'     => $entity->getIsActive(),  // 1/0
        ]);

        $entity->setId((int)$this->pdo->lastInsertId());
        return $entity;
    }

    /** Édition complète (au besoin) */
    public function update(VendorShippingOption $entity): bool
    {
        $sql = "UPDATE {$this->table}
               SET logo        = :logo,
                   name        = :name,
                   address     = :address,
                   description = :description,
                   owner_user_id = :owner,
                   phone       = :phone,
                   email       = :email,
                   website     = :website,
                   country     = :country,
                   city        = :city,
                   latitude    = :lat,
                   longitude   = :lng,
                   is_active   = :active,
                   updated_at  = CURRENT_TIMESTAMP
             WHERE id = :id";

        return $this->executeQuery($sql, [
            'id'          => (int)$entity->getId(),
            'logo'        => $entity->getImage(),
            'name'        => $entity->getName(),
            'address'     => $entity->getAddress(),
            'description' => $entity->getDescription(),
            'owner'       => $entity->getOwnerUserId(),
            'phone'       => $entity->getPhone(),
            'email'       => $entity->getEmail(),
            'website'     => $entity->getWebsite(),
            'country'     => $entity->getCountry(),
            'city'        => $entity->getCity(),
            'lat'         => $entity->getLatitude(),
            'lng'         => $entity->getLongitude(),
            'active'      => $entity->getIsActive(),
        ]);
    }

    // Domain/Model/BaseRepository.php
    protected function fetchRow(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }


    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $this->executeQuery($sql, ['id' => $id]);
    }

    public function findById(int $id): ?VendorShippingOption
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $row = $this->fetchRow($sql, ['id' => $id]);
        return $row ? $this->map($row) : null;
    }

    public function findByName(string $name): ?VendorShippingOption
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $row = $this->fetchRow($sql, ['name' => $name]);
        return $row ? $this->map($row) : null;
    }

    /** Legacy: options + destinations groupées (pour /api/get-vendorShippingOptions) */
    public function findAllWithDestinations(): array
    {
        $sql = "
            SELECT 
                o.id   AS option_id,
                o.name,
                o.logo,
                o.address,
                o.description,
                d.city,
                d.country
            FROM {$this->table} o
            LEFT JOIN vendor_shipping_destinations d ON o.id = d.shipping_option_id
            ORDER BY o.name ASC
        ";
        $rows = $this->executeQueryArray($sql);
        $grouped = [];
        foreach ($rows as $r) {
            $id = (int)$r['option_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id'          => $id,
                    'name'        => $r['name'],
                    'logo'        => $r['logo'],
                    'address'     => $r['address'],
                    'description' => $r['description'],
                    'destinations' => [],
                ];
            }
            if (!empty($r['city']) && !empty($r['country'])) {
                $grouped[$id]['destinations'][] = [
                    'city' => $r['city'],
                    'country' => $r['country']
                ];
            }
        }
        return array_values($grouped);
    }

    /* ===== Pagination & search pour /api/vendorShippingOptions ===== */

    public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");
        return (int)$stmt->fetchColumn();
    }

    /** @return VendorShippingOption[] */
    public function listPaged(int $limit, int $offset): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function countSearch(string $q): int
    {
        $like = '%' . $q . '%';
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE name LIKE :q OR address LIKE :q OR description LIKE :q";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /** @return VendorShippingOption[] */
    public function searchPaged(string $q, int $limit, int $offset): array
    {
        $like = '%' . $q . '%';
        $sql = "SELECT * FROM {$this->table}
                WHERE name LIKE :q OR address LIKE :q OR description LIKE :q
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function getDashboardSummary(int $agencyId): array
    {
        // KPIs 30 jours
        $sqlKpis = "SELECT shipments_30d, revenue_cents_30d, avg_hours_delivery, delivered_cnt, failed_cnt
                FROM v_agency_kpis_30d WHERE agency_id = :aid";
        $kpis = $this->fetchRow($sqlKpis, ['aid' => $agencyId]) ?? [
            'shipments_30d' => 0,
            'revenue_cents_30d' => 0,
            'avg_hours_delivery' => null,
            'delivered_cnt' => 0,
            'failed_cnt' => 0
        ];

        // Statuts en cours
        $sqlByStatus = "SELECT status, COUNT(*) as c
                    FROM agency_shipments
                    WHERE agency_id = :aid AND created_at >= CURRENT_DATE - INTERVAL 30 DAY
                    GROUP BY status";
        $rows = $this->executeQueryArray($sqlByStatus, ['aid' => $agencyId]);
        $byStatus = [];
        foreach ($rows as $r) $byStatus[$r['status']] = (int)$r['c'];

        // Litiges ouverts
        $openClaims = (int)$this->pdo
            ->query("SELECT COUNT(*) FROM agency_claims WHERE agency_id = " . (int)$agencyId . " AND status IN ('open','investigating')")
            ->fetchColumn();

        // Prochain payout
        $payout = $this->fetchRow(
            "SELECT id, period_start, period_end, total_shipments, total_due_cents, currency, status
         FROM agency_payouts
         WHERE agency_id = :aid AND status IN ('pending','processing')
         ORDER BY period_end DESC LIMIT 1",
            ['aid' => $agencyId]
        );

        return [
            'kpis_30d'   => $kpis,
            'by_status'  => $byStatus,
            'open_claims' => $openClaims,
            'next_payout' => $payout,
        ];
    }

    public function listServing(string $country, string $city): array
    {
        $sql = "SELECT o.*
            FROM {$this->table} o
            JOIN vendor_shipping_destinations d
              ON d.shipping_option_id = o.id
            WHERE o.is_active = 1
              AND d.country = :c
              AND (d.city = :city OR d.city IS NULL OR d.city = '')
            ORDER BY o.name ASC";
        $rows = $this->executeQueryArray($sql, ['c' => strtoupper($country), 'city' => trim($city)]);
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function listServingByIds(string $country, string $city, array $ids): array
    {
        if (empty($ids)) return [];
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT o.*
            FROM {$this->table} o
            JOIN vendor_shipping_destinations d ON d.shipping_option_id = o.id
            WHERE o.is_active = 1
              AND o.id IN ($in)
              AND d.country = ?
              AND (d.city = ? OR d.city IS NULL OR d.city = '')
            ORDER BY o.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $bind = array_map('intval', $ids);
        $bind[] = strtoupper($country);
        $bind[] = trim($city);
        $stmt->execute($bind);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) return [];
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE id IN ($in) AND is_active = 1 ORDER BY name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_map('intval', $ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function listServingByIdsRelaxed(string $country, string $city, array $ids): array
    {
        if (empty($ids)) return [];
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT o.*,
               CASE WHEN EXISTS (
                   SELECT 1 FROM vendor_shipping_destinations d
                   WHERE d.shipping_option_id = o.id
                     AND d.country = ?
                     AND (d.city = ? OR d.city IS NULL OR d.city = '')
               ) THEN 1 ELSE 0 END AS serves_dest
        FROM {$this->table} o
        WHERE o.is_active = 1
          AND o.id IN ($in)
        ORDER BY o.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $bind = array_merge([strtoupper($country), trim($city)], array_map('intval', $ids));
        $stmt->execute($bind);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => $this->map($r), $rows);
    }


    public function getHeader(int $agencyId): array
    {
        $row = $this->fetchRow(
            "SELECT id, name, logo AS image, city, country 
         FROM {$this->table} WHERE id = :id",
            ['id' => $agencyId]
        ) ?? [];
        return [
            'id'      => (int)($row['id'] ?? 0),
            'name'    => (string)($row['name'] ?? ''),
            'image'   => (string)($row['image'] ?? ''),
            'city'    => (string)($row['city'] ?? ''),
            'country' => (string)($row['country'] ?? ''),
        ];
    }

    /** Mise à jour partielle, champs whitelistes */
    public function updateFields(int $agencyId, array $data): bool
    {
        if ($agencyId <= 0) return false;

        $whitelist = [
            'logo',
            'name',
            'address',
            'description',
            'phone',
            'email',
            'website',
            'country',
            'city',
            'latitude',
            'longitude',
            'is_active'
        ];

        $sets   = [];
        $params = ['id' => $agencyId];

        foreach ($whitelist as $k) {
            if (array_key_exists($k, $data)) {
                $sets[] = "{$k} = :{$k}";
                $params[$k] = $data[$k];
            }
        }

        if (empty($sets)) return true; // rien à faire

        $sql = "UPDATE {$this->table}
                   SET " . implode(',', $sets) . ",
                       updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";

        return $this->executeQuery($sql, $params);
    }

    /** Destinations: liste */
    public function listDestinations(int $agencyId): array
    {
        $sql = "SELECT id, shipping_option_id, country, city
                  FROM vendor_shipping_destinations
                 WHERE shipping_option_id = :id
                 ORDER BY country, city";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $agencyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Destinations: remplace tout (transaction).
     * $rows = [ [country=>'UG', city=>'Kampala'], ... ]
     */
    public function replaceDestinations(int $agencyId, array $rows): bool
    {
        if ($agencyId <= 0) return false;

        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM vendor_shipping_destinations WHERE shipping_option_id = :id");
            $del->execute(['id' => $agencyId]);

            if (!empty($rows)) {
                $ins = $pdo->prepare(
                    "INSERT INTO vendor_shipping_destinations (shipping_option_id, country, city)
                     VALUES (:id, :country, :city)"
                );
                foreach ($rows as $r) {
                    $country = strtoupper(trim((string)($r['country'] ?? '')));
                    $city    = trim((string)($r['city'] ?? ''));
                    if ($country === '' || strlen($country) !== 2) continue; // ISO-2
                    // ville vide autorisée (wildcard)
                    $ins->execute(['id' => $agencyId, 'country' => $country, 'city' => $city]);
                }
            }

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('replaceDestinations error: ' . $e->getMessage());
            return false;
        }
    }

    /** Retourne la ligne brute de l’agence pour un owner donné (ou null) */
    public function findOneByOwner(int $ownerUserId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE owner_user_id = :uid LIMIT 1";
        return $this->fetchRow($sql, ['uid' => $ownerUserId]);
    }

    /** Retourne l’ID de l’agence pour un owner donné (ou null) */
    public function findIdByOwner(int $ownerUserId): ?int
    {
        $row = $this->findOneByOwner($ownerUserId);
        return $row ? (int)$row['id'] : null;
    }

    /** Vrai si un owner a déjà une agence */
    public function existsByOwner(int $ownerUserId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$this->table} WHERE owner_user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $ownerUserId]);
        return (bool)$stmt->fetchColumn();
    }

    /** Variante orientée domaine : renvoie une entité (ou null) */
    public function findOneEntityByOwner(int $ownerUserId): ?VendorShippingOption
    {
        $row = $this->findOneByOwner($ownerUserId);
        return $row ? $this->map($row) : null;
    }
}
