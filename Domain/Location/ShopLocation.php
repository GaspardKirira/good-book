<?php

namespace Domain\Location;

class ShopLocation
{
    private int $user_id;
    private string $address;
    private float $latitude;
    private float $longitude;
    private bool $is_public;
    private \DateTime $created_at;
    private \DateTime $updated_at;

    public function __construct(
        int $user_id,
        string $address,
        float $latitude,
        float $longitude,
        bool $is_public = true,
        ?\DateTime $created_at = null,
        ?\DateTime $updated_at = null
    ) {
        $this->user_id = $user_id;
        $this->address = $address;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->is_public = $is_public;
        $this->created_at = $created_at ?? new \DateTime();
        $this->updated_at = $updated_at ?? new \DateTime();
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function isPublic(): bool
    {
        return $this->is_public;
    }

    public function setIsPublic(bool $is_public): void
    {
        $this->is_public = $is_public;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): void
    {
        $this->updated_at = $updated_at;
    }
}
