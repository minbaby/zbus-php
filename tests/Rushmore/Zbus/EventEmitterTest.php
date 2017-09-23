<?php

namespace TEst\Rushmore\Zbus;

use Mockery\Mock;
use Test\Mock\EventEmitterImpl;
use Test\TestCase;

class EventEmitterTest extends TestCase
{
    public function testOn()
    {
        $impl = new EventEmitterImpl();

        $callable = $this->getReturnCallback('event');

        $impl->on('event', $callable);

        $listeners = $this->getProperty($impl, 'listeners');
        $callback = $listeners['event'][0];
        $this->assertEquals($callable, $callback);
        $this->assertEquals('event', $callback());
    }

    public function testOnce()
    {
        $impl = new EventEmitterImpl();

        $callable = $this->getReturnCallback('event');

        $impl->once('event', $callable);

        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $callback = $onceListeners['event'][0];
        $this->assertEquals($callable, $callback);
        $this->assertEquals('event', $callback());
    }


    public function testRemoveListener()
    {
        $impl = new EventEmitterImpl();

        $callable11 = $this->getReturnCallback('event11');
        $callable12 = $this->getReturnCallback('event12');
        $callable21 = $this->getReturnCallback('event21');
        $callable22 = $this->getReturnCallback('event22');

        $impl->on('event1', $callable11);
        $impl->on('event1', $callable12);
        $impl->once('event2', $callable21);
        $impl->once('event2', $callable22);

        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $listeners = $this->getProperty($impl, 'listeners');
        $this->assertCount(2, $onceListeners['event2']);
        $this->assertCount(2, $listeners['event1']);

        // on
        $impl->removeListener('event1', $callable11);
        $listeners = $this->getProperty($impl, 'listeners');
        $callback = $listeners['event1'][1]; // 索引没有重置
        $this->assertEquals('event12', $callback());
        $this->assertCount(1, $listeners['event1']);

        $impl->removeListener('event1', $callable12);
        $listeners = $this->getProperty($impl, 'listeners');
        $this->assertFalse(isset($listeners['event1']));


        // once
        $impl->removeListener('event2', $callable21);
        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $callback = $onceListeners['event2'][1]; // 索引没有重置
        $this->assertEquals('event22', $callback());
        $this->assertCount(1, $onceListeners['event2']);

        $impl->removeListener('event2', $callable22);
        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $this->assertFalse(isset($onceListeners['event1']));
    }

    public function testRemoveAllListeners()
    {
        $impl = new EventEmitterImpl();

        $callable11 = $this->getReturnCallback('event11');
        $callable12 = $this->getReturnCallback('event12');
        $callable21 = $this->getReturnCallback('event21');
        $callable22 = $this->getReturnCallback('event22');

        $impl->on('event1', $callable11);
        $impl->on('event1', $callable12);
        $impl->once('event2', $callable21);
        $impl->once('event2', $callable22);

        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $listeners = $this->getProperty($impl, 'listeners');
        $this->assertCount(2, $onceListeners['event2']);
        $this->assertCount(2, $listeners['event1']);

        // remove event1
        $impl->removeAllListeners('event1');

        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $listeners = $this->getProperty($impl, 'listeners');

        $this->assertFalse(isset($listeners['event1']));
        $this->assertCount(2, $onceListeners['event2']);

        // remove event2
        $impl->removeAllListeners('event2');

        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $listeners = $this->getProperty($impl, 'listeners');

        $this->assertFalse(isset($listeners['event1']));
        $this->assertFalse(isset($onceListeners['event2']));

        //rmeove all
        $impl->on('event1', $callable11);
        $impl->on('event1', $callable12);
        $impl->once('event2', $callable21);
        $impl->once('event2', $callable22);

        $impl->removeAllListeners();

        $this->assertFalse(isset($listeners['event1']));
        $this->assertFalse(isset($onceListeners['event2']));
    }

    public function testListeners()
    {
        $impl = new EventEmitterImpl();

        $callable11 = $this->getReturnCallback('event11');
        $callable12 = $this->getReturnCallback('event12');
        $callable21 = $this->getReturnCallback('event21');
        $callable22 = $this->getReturnCallback('event22');

        //rmeove all
        $impl->on('event1', $callable11);
        $impl->on('event1', $callable12);
        $impl->once('event2', $callable21);
        $impl->once('event2', $callable22);

        list($c11, $c12) = $impl->listeners('event1');
        list($c21, $c22) = $impl->listeners('event2');

        $this->assertEquals($callable11(), $c11());
        $this->assertEquals($callable12(), $c12());
        $this->assertEquals($callable21(), $c21());
        $this->assertEquals($callable22(), $c22());


        $impl = new EventEmitterImpl();

        $this->assertEquals([], $impl->listeners('test'));
    }

    public function testEmit()
    {
        $impl = new EventEmitterImpl();

        $callable11 = $this->getEchoCallback('event11');
        $callable21 = $this->getEchoCallback('event21');

        $impl->on('event1', $callable11);
        $impl->once('event2', $callable21);

        $impl->emit('event1', ['yes']);
        $this->expectOutputString("event11yes");

        $impl->emit('event2', ['yes2']);
        $this->expectOutputString("event11yesevent21yes2");

        $onceListeners = $this->getProperty($impl, 'onceListeners');
        $listeners = $this->getProperty($impl, 'listeners');

        $this->assertCount(1, $listeners['event1']);
        $this->assertFalse(isset($onceListeners['event2']));
    }
}

