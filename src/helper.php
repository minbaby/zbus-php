<?php

use Rushmore\Zbus\ConsumeGroup;
use Rushmore\Zbus\Message;

/**
 * borrowed from: https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
 * @return string
 */
function uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

/**
 * @param $topicCtrl
 * @param null $cmd
 * @return Message
 * @throws Exception
 */
function buildMessage($topicCtrl, $cmd = null)
{
    if (is_string($topicCtrl)) {
        $msg = new Message();
        $msg->topic = $topicCtrl;
    } elseif (is_object($topicCtrl) && get_class($topicCtrl) == Message::class) {
        $msg = $topicCtrl;
    } elseif (is_object($topicCtrl) && get_class($topicCtrl) == ConsumeGroup::class) {
        $msg = new Message();
        /** @var ConsumeGroup $topicCtrl */
        $topicCtrl->toMessage($msg);
    } elseif (is_array($topicCtrl)) {
        $msg = new Message();
        foreach ($topicCtrl as $key => $val) {
            $msg->setHeader($key, $val);
        }
    } else {
        throw new Exception("invalid: $topicCtrl"); // TODO 如果这里是 object 就会出现错误
    }
    $msg->cmd = $cmd;
    return $msg;
}
