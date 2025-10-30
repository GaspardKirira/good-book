<?php

namespace Domain\VendorShippingOptions;

use JsonSerializable;

class VendorShippingOption implements JsonSerializable
{
    /** @var int|null */
    protected ?int $id = null;

    protected ?string $image = null;

    protected string $name;
    protected string $address;
    protected string $description;

    protected ?int $owner_user_id = null;
    protected ?string $phone      = null;
    protected ?string $email      = null;
    protected ?string $website    = null;
    protected ?string $country    = null; // ISO-2 (UG, CD, …)
    protected ?string $city       = null;
    protected ?float  $latitude   = null;
    protected ?float  $longitude  = null;
    protected int     $is_active  = 1;

    /** Timestamps */
    protected ?string $created_at  = null;
    protected ?string $updated_at  = null;
    protected ?string $verified_at = null;

    protected ?int $serves_dest = null;

    /**
     * Compat constructeur existant
     */
    public function __construct(?string $image, string $name, string $address, string $description)
    {
        $this->image       = $image;
        $this->name        = $name;
        $this->address     = $address;
        $this->description = $description;
    }

    /* ===== Id ===== */
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /* ===== Image / Logo ===== */
    public function getImage(): ?string
    {
        return $this->image;
    }
    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    /* ===== Nom / Adresse / Description ===== */
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /* ===== Propriétaire ===== */
    public function getOwnerUserId(): ?int
    {
        return $this->owner_user_id;
    }
    public function setOwnerUserId(?int $ownerUserId): void
    {
        $this->owner_user_id = $ownerUserId;
    }

    /* ===== Contact ===== */
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }
    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    /* ===== Localisation ===== */
    public function getCountry(): ?string
    {
        return $this->country;
    }
    public function setCountry(?string $country): void
    {
        $this->country = $country ? strtoupper($country) : null;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }
    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }
    public function setLatitude(?float $lat): void
    {
        $this->latitude = $lat;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }
    public function setLongitude(?float $lng): void
    {
        $this->longitude = $lng;
    }

    /* ===== Statut actif ===== */
    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }
    public function getIsActive(): int
    {
        return $this->is_active;
    }       // compat éventuelle
    public function setIsActive(bool|int $active): void
    {
        $this->is_active = (int)$active;
    }

    /* ===== Dates ===== */
    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }
    public function setCreatedAt(?string $ts): void
    {
        $this->created_at = $ts;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }
    public function setUpdatedAt(?string $ts): void
    {
        $this->updated_at = $ts;
    }

    public function getVerifiedAt(): ?string
    {
        return $this->verified_at;
    }
    public function setVerifiedAt(?string $ts): void
    {
        $this->verified_at = $ts;
    }

    public function getServesDest(): ?int
    {
        return $this->serves_dest;
    }
    public function setServesDest(?int $v): void
    {
        $this->serves_dest = is_null($v) ? null : (int)$v;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'logo'          => $this->image,
            'name'          => $this->name,
            'address'       => $this->address,
            'description'   => $this->description,
            'owner_user_id' => $this->owner_user_id,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'website'       => $this->website,
            'country'       => $this->country,
            'city'          => $this->city,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'verified_at'   => $this->verified_at,
            'serves_dest' => $this->serves_dest,
        ];
    }

    /** Implémentation JsonSerializable */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Hydrate depuis un tableau (optionnel)
     */
    public static function fromArray(array $row): self
    {
        $e = new self(
            $row['logo'] ?? $row['image'] ?? null,
            (string)($row['name'] ?? ''),
            (string)($row['address'] ?? ''),
            (string)($row['description'] ?? '')
        );

        if (isset($row['id']))             $e->setId((int)$row['id']);
        if (isset($row['owner_user_id']))  $e->setOwnerUserId((int)$row['owner_user_id']);
        if (isset($row['phone']))          $e->setPhone($row['phone']);
        if (isset($row['email']))          $e->setEmail($row['email']);
        if (isset($row['website']))        $e->setWebsite($row['website']);

        if (isset($row['country']))        $e->setCountry($row['country']);
        if (isset($row['city']))           $e->setCity($row['city']);
        if (array_key_exists('latitude', $row))   $e->setLatitude($row['latitude'] !== null ? (float)$row['latitude'] : null);
        if (array_key_exists('longitude', $row))  $e->setLongitude($row['longitude'] !== null ? (float)$row['longitude'] : null);

        if (isset($row['is_active']))      $e->setIsActive((int)$row['is_active']);
        if (isset($row['created_at']))     $e->setCreatedAt($row['created_at']);
        if (isset($row['updated_at']))     $e->setUpdatedAt($row['updated_at']);
        if (isset($row['verified_at']))    $e->setVerifiedAt($row['verified_at']);
        if (isset($row['serves_dest'])) $e->setServesDest((int)$row['serves_dest']);

        return $e;
    }
}
