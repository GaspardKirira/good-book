<?php

namespace Domain\Countries;

use Domain\Model\BaseRepository;
use Domain\Model\Table;

class CountryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Table::COUNTRIES);
    }

    public function save(Country $entity): Country
    {
        $sql = "INSERT INTO {$this->table} (name) 
            VALUES (:name)";

        $this->executeQuery($sql, [
            'name' => $entity->getName()
        ]);

        $entity->setId($this->pdo->lastInsertId());

        return $entity;
    }

    public function findByName(string $name): ?Country
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $entity = $this->fetchOne($sql, ['name' => $name]);
        return $entity ? $entity : null;
    }

    public function findById(int $id): ?Country
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $entity = $this->fetchOne($sql, ['id' => $id]);
        return $entity ? $entity : null;
    }

    protected function map(array $data): Country
    {
        $enitty = new Country(
            $data['name']
        );
        $enitty->setId($data['id']);

        return $enitty;
    }

    public function update(Country $enitty): void
    {
        $sql = "UPDATE {$this->table} SET 
            name = :name,
            updated_at = :updated_at 
            WHERE id = :id";

        $this->executeQuery($sql, [
            'id' => $enitty->getId(),
            'name' => $enitty->getName()
        ]);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $this->executeQuery($sql, ['id' => $id]);
    }
}
