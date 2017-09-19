<?php

namespace Rushmore\Zbus;

trait EventEmitter
{
    protected $listeners = [];
    protected $onceListeners = [];

    public function on($event, callable $listener)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
        return $this;
    }

    public function once($event, callable $listener)
    {
        if (!isset($this->onceListeners[$event])) {
            $this->onceListeners[$event] = [];
        }
        $this->onceListeners[$event][] = $listener;
        return $this;
    }

    public function removeListener($event, callable $listener)
    {
        if (isset($this->listeners[$event])) {
            $index = \array_search($listener, $this->listeners[$event], true);
            if (false !== $index) {
                unset($this->listeners[$event][$index]);
                if (\count($this->listeners[$event]) === 0) {
                    unset($this->listeners[$event]);
                }
            }
        }
        if (isset($this->onceListeners[$event])) {
            $index = \array_search($listener, $this->onceListeners[$event], true);
            if (false !== $index) {
                unset($this->onceListeners[$event][$index]);
                if (\count($this->onceListeners[$event]) === 0) {
                    unset($this->onceListeners[$event]);
                }
            }
        }
    }

    public function removeAllListeners($event = null)
    {
        if ($event !== null) {
            unset($this->listeners[$event]);
        } else {
            $this->listeners = [];
        }
        if ($event !== null) {
            unset($this->onceListeners[$event]);
        } else {
            $this->onceListeners = [];
        }
    }

    public function listeners($event)
    {
        return array_merge(
            isset($this->listeners[$event]) ? $this->listeners[$event] : [],
            isset($this->onceListeners[$event]) ? $this->onceListeners[$event] : []
        );
    }

    public function emit($event, array $arguments = [])
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listener(...$arguments);
            }
        }
        if (isset($this->onceListeners[$event])) {
            $keys = array_keys($this->onceListeners[$event]);
            foreach ($keys as $key) {
                $listener = $this->onceListeners[$event][$key];
                $listener(...$arguments);
                unset($this->onceListeners[$event][$key]);
            }
            if (count($this->onceListeners[$event]) === 0) {
                unset($this->onceListeners[$event]);
            }
        }
    }
}
