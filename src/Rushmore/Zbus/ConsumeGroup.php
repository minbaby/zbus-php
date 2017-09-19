<?php

namespace Rushmore\Zbus;

class ConsumeGroup {
    public $topic;        //topic of the group
    public $groupName;
    public $filter;
    public $startCopy;
    public $startOffset;  //create group start from offset, msgId to check valid
    public $startMsgId;
    public $startTime;    //unix time, create group start from time

    function __construct($groupName = null, $filter = null) {
        $this->groupName = $groupName;
        $this->filter = $filter;
    }

    public function fromMessage($msg){
        $this->topic = $msg->getHeader(Protocol::TOPIC);
        $this->groupName = $msg->getHeader(Protocol::CONSUME_GROUP);
        $this->filter = $msg->getHeader(Protocol::GROUP_FILTER);
        $this->startCopy = $msg->getHeader(Protocol::GROUP_START_COPY);
        $this->startOffset = $msg->getHeader(Protocol::GROUP_START_OFFSET);
        $this->startMsgId = $msg->getHeader(Protocol::GROUP_START_MSGID);
        $this->startTime = $msg->getHeader(Protocol::GROUP_START_TIME);
    }

    public function toMessage($msg){
        $msg->setHeader(Protocol::TOPIC, $this->topic);
        $msg->setHeader(Protocol::CONSUME_GROUP, $this->groupName);
        $msg->setHeader(Protocol::GROUP_FILTER, $this->filter);
        $msg->setHeader(Protocol::GROUP_START_COPY, $this->startCopy);
        $msg->setHeader(Protocol::GROUP_START_OFFSET, $this->startOffset);
        $msg->setHeader(Protocol::GROUP_START_MSGID, $this->startMsgId);
        $msg->setHeader(Protocol::GROUP_START_TIME, $this->startTime);
    }
}
