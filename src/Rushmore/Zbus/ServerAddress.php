<?php
/**
 * Created by PhpStorm.
 * User: zhangshaomin
 * Date: 2017/9/19
 * Time: 15:12
 */

namespace Rushmore\Zbus;

class ServerAddress {
    public $address;
    public $ssl_enabled;

    function __construct($address, $ssl_enabled= false) {
        if(is_string($address)){
            $this->address = $address;
            $this->ssl_enabled= $ssl_enabled;
            return;
        } else if (is_array($address)){
            $this->address = $address['address'];
            $this->ssl_enabled= $address['sslEnabled'];
        } else if (is_object($address) && get_class($address)== ServerAddress::class){
            $this->address = $address->address;
            $this->ssl_enabled= $address->ssl_enabled;
        } else {
            throw new Exception("address not support");
        }
    }

    public function __toString(){
        if($this->ssl_enabled){
            return "[SSL]".$this->address;
        }
        return $this->address;
    }
}