<?php

namespace Domain\SendOptions;

class SendOption
{
    private $id;
    private $user_id;
    private $vendor_shipping_option;
    private $active;

    public function __construct($user_id, $vendor_shipping_option)
    {
        $this->user_id = $user_id;
        $this->vendor_shipping_option = $vendor_shipping_option;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'vendor_shipping_option_id' => $this->vendor_shipping_option
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function getVendorShippingOption()
    {
        return $this->vendor_shipping_option;
    }

    public function setVendorShippingOption($vendor_shipping_option)
    {
        $this->vendor_shipping_option = $vendor_shipping_option;
    }

    public function getIsActive()
    {
        return $this->active;
    }

    public function setIsActive($active)
    {
        $this->active = $active;
    }
}
