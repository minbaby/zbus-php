<?php

namespace Rushmore\Zbus\Mq;

use Rushmore\Zbus\Message;

interface BaseClient
{
    /**
     * @param Message $msg
     * @param int $timeout
     * @return mixed
     */
    public function route(Message $msg, $timeout = 3);
}