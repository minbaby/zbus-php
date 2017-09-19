<?php

namespace Rushmore\Zbus;

use Exception;

class ServerAddress
{
    public $address;
    public $ssl_enabled;

    public function __construct($address, $ssl_enabled = false)
    {
        if (is_string($address)) {
            $this->address = $address;
            $this->ssl_enabled = $ssl_enabled;
            return;
        } elseif (is_array($address)) {
            $this->address = $address['address'];
            $this->ssl_enabled = $address['sslEnabled'];
        } elseif (is_object($address) && get_class($address) == ServerAddress::class) {
            $this->address = $address->address;
            $this->ssl_enabled = $address->ssl_enabled;
        } else {
            throw new Exception("address not support");
        }
    }

    public function __toString()
    {
        if ($this->ssl_enabled) {
            return "[SSL]".$this->address;
        }
        return $this->address;
    }
}
