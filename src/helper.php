<?php

//borrowed from: https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
function uuid()
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function buildMessage($topicCtrl, $cmd = null)
{
    if (is_string($topicCtrl)) {
        $msg = new Message();
        $msg->topic = $topicCtrl;
    } elseif (is_object($topicCtrl) && get_class($topicCtrl) == Message::class) {
        $msg = $topicCtrl;
    } elseif (is_object($topicCtrl) && get_class($topicCtrl) == ConsumeGroup::class) {
        $msg = new Message();
        $topicCtrl->toMessage($msg);
    } elseif (is_array($topicCtrl)) {
        $msg = new Message();
        foreach ($topicCtrl as $key => $val) {
            $msg->setHeader($key, $val);
        }
    } else {
        throw new Exception("invalid: $topicCtrl");
    }
    $msg->cmd = $cmd;
    return $msg;
}
