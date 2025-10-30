<?php

namespace Domain\Cities;

class City
{
    private $id;
    private $name;
    private $country_id;

    public function __construct($name, $country_id)
    {
        $this->name = $name;
        $this->country_id = $country_id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getCountryId()
    {
        return $this->country_id;
    }

    public function setCountryId($country_id)
    {
        $this->country_id = $country_id;
    }
}
