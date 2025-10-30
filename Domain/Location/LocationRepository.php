<?php

namespace Domain\Location;

use Domain\Model\BaseRepository;
use Domain\Model\Table;

class LocationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Table::USER_LOCATION);
        $this->table = Table::USER_LOCATION;
    }

    public function save(Location $entity): Location
    {
        $sql = "INSERT INTO {$this->table} (user_id, country_id, city_id, show_city) 
        VALUES (:user_id, :country_id, :city_id, :show_city)";

        $this->executeQuery($sql, [
            'user_id' => $entity->getUserId(),
            'country_id' => $entity->getCountryId(),
            'city_id' => $entity->getCityId(),
            'show_city' => $entity->getShowCity()
        ]);
        return $entity;
    }

    public function update(Location $entity): bool
    {
        $sql = "UPDATE {$this->table} 
            SET country_id = :country_id, city_id = :city_id, show_city = :show_city
            WHERE user_id = :user_id";

        return $this->executeQuery($sql, [
            'user_id' => $entity->getUserId(),
            'country_id' => $entity->getCountryId(),
            'city_id' => $entity->getCityId(),
            'show_city' => $entity->getShowCity()
        ]);
    }

    public function findByName(string $name): ?Location
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $entity = $this->fetchOne($sql, ['name' => $name]);
        return $entity ? $entity : null;
    }

    public function findById(int $user_id): ?Location
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $entity = $this->fetchOne($sql, ['user_id' => $user_id]);
        return $entity ? $entity : null;
    }

    protected function map(array $data): Location
    {
        $enitty = new Location(
            $data['user_id'],
            $data['city_id'],
            $data['country_id'],
            $data['show_city']
        );
        return $enitty;
    }

    public function findByUserId(int $userId): ?Location
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $entity = $this->fetchOne($sql, ['user_id' => $userId]);
        return $entity ? $entity : null;
    }
}
