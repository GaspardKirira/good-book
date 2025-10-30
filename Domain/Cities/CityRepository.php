<?php

namespace Domain\Cities;

use Domain\Model\BaseRepository;
use Domain\Model\Table;
use PDO;
use PDOException;

class CityRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Table::CITIES);
    }

    public function save(City $entity): City
    {
        $sql = "INSERT INTO {$this->table} (name, country_id) 
            VALUES (:name, :country_id)";

        $this->executeQuery($sql, [
            'name' => $entity->getName(),
            'country_id' => $entity->getCountryId()
        ]);

        $entity->setId($this->pdo->lastInsertId());

        return $entity;
    }

    public function findByName(string $name): ?City
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $entity = $this->fetchOne($sql, ['name' => $name]);
        return $entity ? $entity : null;
    }

    public function findById(int $id): ?City
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $entity = $this->fetchOne($sql, ['id' => $id]);
        return $entity ? $entity : null;
    }

    protected function map(array $data): City
    {
        $enitty = new City(
            $data['name'],
            $data['country_id']
        );
        $enitty->setId($data['id']);

        return $enitty;
    }

    public function update(City $enitty): void
    {
        $sql = "UPDATE {$this->table} SET 
            name = :name,
            country_id = :country_id
            WHERE id = :id";

        $this->executeQuery($sql, [
            'id' => $enitty->getId(),
            'name' => $enitty->getName(),
            'country_id' => $enitty->getCountryId()
        ]);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $this->executeQuery($sql, ['id' => $id]);
    }

    public function findByCountryId(int $countryId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE country_id = :country_id ORDER BY name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['country_id' => $countryId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $entityList = [];
        foreach ($results as $row) {
            $entityList[] = $this->map($row);
        }

        return $entityList;
    }
}
