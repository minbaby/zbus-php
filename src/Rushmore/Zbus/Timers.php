<?php
/**
 * Created by PhpStorm.
 * User: zhangshaomin
 * Date: 2017/9/19
 * Time: 15:26
 */

namespace Rushmore\Zbus;

final class Timers {
    private $time;
    private $timers;
    private $scheduler;

    public function __construct() {
        $this->timers = new SplObjectStorage();
        $this->scheduler = new SplPriorityQueue();
    }

    public function updateTime() {
        return $this->time = microtime(true);
    }

    public function getTime() {
        return $this->time ?: $this->updateTime();
    }

    public function add(Timer $timer) {
        $interval = $timer->getInterval();
        $scheduledAt = $interval + microtime(true);

        $this->timers->attach($timer, $scheduledAt);
        $this->scheduler->insert($timer, -$scheduledAt);
    }

    public function contains(Timer $timer) {
        return $this->timers->contains($timer);
    }

    public function cancel(Timer $timer) {
        $this->timers->detach($timer);
    }

    public function getFirst() {
        while ($this->scheduler->count()) {
            $timer = $this->scheduler->top();

            if ($this->timers->contains($timer)) {
                return $this->timers[$timer];
            }

            $this->scheduler->extract();
        }

        return null;
    }

    public function isEmpty() {
        return count($this->timers) === 0;
    }

    public function tick() {
        $time = $this->updateTime();
        $timers = $this->timers;
        $scheduler = $this->scheduler;

        while (!$scheduler->isEmpty()) {
            $timer = $scheduler->top();

            if (!isset($timers[$timer])) {
                $scheduler->extract();
                $timers->detach($timer);

                continue;
            }

            if ($timers[$timer] >= $time) {
                break;
            }

            $scheduler->extract();
            call_user_func($timer->getCallback(), $timer);

            if ($timer->isPeriodic() && isset($timers[$timer])) {
                $timers[$timer] = $scheduledAt = $timer->getInterval() + $time;
                $scheduler->insert($timer, -$scheduledAt);
            } else {
                $timers->detach($timer);
            }
        }
    }
}