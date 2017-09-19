<?php

namespace Rushmore\Zbus;

class BrokerRouteTable
{
    public $topicTable = [];   //{ TopicName => [TopicInfo] }
    public $serverTable = [];  //{ ServerAddress => ServerInfo }
    public $votesTable = [];   //{ TrackerAddress => Vote } , Vote=(version, servers)
    public $voteFactor = 0.5;


    private $votedTrackers = []; // { TrackerAddress => true }

    public function updateTracker($trackerInfo)
    {
        //1) Update votes
        $trackerAddress = new ServerAddress($trackerInfo['serverAddress']);
        $vote = @$this->votesTable[$trackerAddress];
        $this->votedTrackers[(string)$trackerAddress] = true;

        if ($vote && $vote['version'] >= $trackerInfo['infoVersion']) {
            return;
        }
        $servers = [];
        $serverTable = $trackerInfo['serverTable'];
        foreach ($serverTable as $key => $serverInfo) {
            array_push($servers, new ServerAddress($serverInfo['serverAddress']));
        }

        $this->votesTable[(string)$trackerAddress] = ['version' => $trackerInfo['infoVersion'], 'servers' => $servers];

        //2) Merge ServerTable
        foreach ($serverTable as $key => $serverInfo) {
            $serverAddress = new ServerAddress($serverInfo['serverAddress']);
            $this->serverTable[(string)$serverAddress] = $serverInfo;
        }

        //3) Purge
        return $this->purge();
    }

    public function removeTracker($trackerAddress)
    {
        $trackerAddress = new ServerAddress($trackerAddress);
        unset($this->votesTable[(string)$trackerAddress]);
        return $this->purge();
    }

    private function purge()
    {
        $toRemove = [];
        $serverTableLocal = $this->serverTable;
        foreach ($serverTableLocal as $key => $server_info) {
            $serverAddress = new ServerAddress($server_info['serverAddress']);
            $count = 0;
            foreach ($this->votesTable as $key => $vote) {
                $servers = $vote['servers'];
                if (in_array((string)$serverAddress, $servers)) {
                    $count++;
                }
            }
            if ($count < count($this->votedTrackers) * $this->voteFactor) {
                array_push($toRemove, $serverAddress);
                unset($serverTableLocal[(string)$serverAddress]);
            }
        }
        $this->serverTable = $serverTableLocal;

        $this->rebuildTopicTable();
        return $toRemove;
    }

    private function rebuildTopicTable()
    {
        $topicTable = [];
        foreach ($this->serverTable as $server_key => $serverInfo) {
            foreach ($serverInfo['topicTable'] as $topicKey => $topicInfo) {
                $topicList = @$topicTable[$topicKey];
                if ($topicList == null) {
                    $topicList = [];
                }
                array_push($topicList, $topicInfo);
                $topicTable[$topicKey] = $topicList;
            }
        }
        $this->topicTable = $topicTable;
    }
}
