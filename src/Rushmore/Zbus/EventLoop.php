<?php
namespace Rushmore\Zbus;

class EventLoop {
    const MICROSECONDS_PER_SECOND = 1000000;

    private $futureTickQueue;
    private $timers;
    private $readStreams = [];
    private $readListeners = [];
    private $writeStreams = [];
    private $writeListeners = [];
    private $running;

    public function __construct() {
        $this->futureTickQueue = new TickQueue();
        $this->timers = new Timers();
    }

    public function addReadStream($stream, callable $listener) {
        $key = (int) $stream;
        if (!isset($this->readStreams[$key])) {
            $this->readStreams[$key] = $stream;
            $this->readListeners[$key] = $listener;
        }
    }

    public function addWriteStream($stream, callable $listener) {
        $key = (int) $stream;
        if (!isset($this->writeStreams[$key])) {
            $this->writeStreams[$key] = $stream;
            $this->writeListeners[$key] = $listener;
        }
    }

    public function removeReadStream($stream) {
        $key = (int) $stream;
        unset(
            $this->readStreams[$key],
            $this->readListeners[$key]
        );
    }

    public function removeWriteStream($stream) {
        $key = (int) $stream;
        unset(
            $this->writeStreams[$key],
            $this->writeListeners[$key]
        );
    }

    public function removeStream($stream) {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    public function addTimer($interval, callable $callback, $periodic=false) {
        $timer = new Timer($interval, $callback, (bool)$periodic);
        $this->timers->add($timer);
        return $timer;
    }

    public function cancelTimer(Timer $timer) {
        $this->timers->cancel($timer);
    }

    public function isTimerActive(Timer $timer) {
        return $this->timers->contains($timer);
    }

    public function futureTick(callable $listener) {
        $this->futureTickQueue->add($listener);
    }

    public function runOnce() {
        $this->run(true);
    }

    public function run($exit_on_empty=false) {
        $this->running = true;
        while ($this->running) {
            $this->futureTickQueue->tick();
            $this->timers->tick();

            // tick queue has pending callbacks ...
            if (!$this->running || !$this->futureTickQueue->isEmpty()) {
                $timeout = 0;

                // There is a pending timer, only block until it is due ...
            } elseif ($scheduledAt = $this->timers->getFirst()) {
                $timeout = $scheduledAt - $this->timers->getTime();
                if ($timeout < 0) {
                    $timeout = 0;
                } else {
                    $timeout = round($timeout * self::MICROSECONDS_PER_SECOND);
                }

                // The only possible event is stream activity, so wait forever ...
            } elseif ($this->readStreams || $this->writeStreams) {
                $timeout = null;
            } else {
                if ($exit_on_empty){
                    break;
                }
                $timeout = round(0.01 * self::MICROSECONDS_PER_SECOND);
            }

            $this->waitForStreamActivity($timeout);
        }
    }

    public function stop() {
        $this->running = false;
    }

    private function waitForStreamActivity($timeout) {
        $read  = $this->readStreams;
        $write = $this->writeStreams;

        $available = $this->streamSelect($read, $write, $timeout);
        if ($available === false) {
            return;
        }

        foreach ($read as $stream) {
            $key = (int) $stream;

            if (isset($this->readListeners[$key])) {
                call_user_func($this->readListeners[$key], $stream, $this);
            }
        }

        foreach ($write as $stream) {
            $key = (int) $stream;

            if (isset($this->writeListeners[$key])) {
                call_user_func($this->writeListeners[$key], $stream, $this);
            }
        }
    }

    protected function streamSelect(array &$read, array &$write, $timeout) {
        if ($read || $write) {
            $except = null;
            // suppress warnings that occur, when stream_select is interrupted by a signal
            return @stream_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
        }

        $timeout && usleep($timeout);
        return 0;
    }
}
