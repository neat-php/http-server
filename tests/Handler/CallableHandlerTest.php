<?php

namespace Neat\Http\Server\Test\Handler;

use Neat\Http\Server\Handler;
use Neat\Http\Server\Handler\CallableHandler;
use PHPUnit\Framework\TestCase;

class CallableHandlerTest extends TestCase
{
    public function testCallable()
    {
        $handler = new CallableHandler($callable = function () {
        });

        $this->assertInstanceOf(Handler::class, $handler);
        $this->assertSame($callable, $handler->callable());
    }

    public function testHandle()
    {
        $handler = new CallableHandler($callable = function () {
        });

        // TODO TEST HANDLE
    }
}
