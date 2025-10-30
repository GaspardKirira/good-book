<?php

namespace Domain\Location;

class Location
{
    private $user_id;
    private $country_id;
    private $city_id;
    private $show_city;

    public function __construct($user_id, $city_id, $country_id = 1, $show_city = 0)
    {
        $this->user_id = $user_id;
        $this->country_id = $country_id;
        $this->city_id = $city_id;
        $this->show_city = $show_city;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function getCountryId()
    {
        return $this->country_id;
    }

    public function setCountryId($country_id)
    {
        $this->country_id = $country_id;
    }

    public function getCityId()
    {
        return $this->city_id;
    }

    public function setCityId($city_id)
    {
        $this->city_id = $city_id;
    }

    public function getShowCity()
    {
        return $this->show_city;
    }

    public function setShowCity($show_city)
    {
        $this->show_city = $show_city;
    }
}
