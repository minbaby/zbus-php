<?php

namespace Rushmore\Zbus;

class Response
{
    public $result;
    public $error;

    public function __construct($result = null, $error = null)
    {
        $this->result = $result;
        $this->error = $error;
    }

    public function __toString()
    {
        return json_encode($this);
    }
}
