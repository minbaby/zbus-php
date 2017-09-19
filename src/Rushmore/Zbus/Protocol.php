<?php

namespace Rushmore\Zbus;

class Protocol {
    //=============================[1] Command Values===============================================
    //MQ Produce/Consume
    const PRODUCE       = "produce";
    const CONSUME       = "consume";
    const ROUTE   	    = "route";     //route back message to sender, designed for RPC
    const RPC   	    = "rpc";       //the same as produce command except rpc set ack false by default

    //Topic control
    const DECLARE_ = "declare";   //declare and empty keywords!!! PHP sucks
    const QUERY    = "query";
    const REMOVE   = "remove";
    const EMPTY_   = "empty";

    //High Availability (HA)
    const TRACK_PUB   = "track_pub";
    const TRACK_SUB   = "track_sub";
    const TRACKER     = "tracker";

    //=============================[2] Parameter Values================================================
    const COMMAND       	   = "cmd";
    const TOPIC         	   = "topic";
    const TOPIC_MASK           = "topic_mask";
    const TAG   	     	   = "tag";
    const OFFSET        	   = "offset";

    const CONSUME_GROUP        = "consume_group";
    const GROUP_START_COPY     = "group_start_copy";
    const GROUP_START_OFFSET   = "group_start_offset";
    const GROUP_START_MSGID    = "group_start_msgid";
    const GROUP_START_TIME     = "group_start_time";
    const GROUP_FILTER         = "group_filter";
    const GROUP_MASK           = "group_mask";
    const CONSUME_WINDOW       = "consume_window";

    const SENDER   			= "sender";
    const RECVER   			= "recver";
    const ID      		    = "id";

    const HOST   		    = "host";
    const ACK      			= "ack";
    const ENCODING 			= "encoding";

    const ORIGIN_ID         = "origin_id";
    const ORIGIN_URL   		= "origin_url";
    const ORIGIN_STATUS     = "origin_status";

    //Security
    const TOKEN   		    = "token";

    const HEARTBEAT   		= "heartbeat";

    const MASK_MEMORY    	 = 1<<0;
    const MASK_RPC    	     = 1<<1;
    const MASK_PROXY    	 = 1<<2;
    const MASK_PAUSE    	 = 1<<3;
    const MASK_EXCLUSIVE 	 = 1<<4;
    const MASK_DELETE_ON_EXIT= 1<<5;
}
