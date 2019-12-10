<?php

namespace Neat\Http\Server\Test\Handler;

use Neat\Http\Request;
use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Handler\CallableHandler;
use Neat\Http\Server\Test\CallableMock;
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
        $request  = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $callable = $this->createMock(CallableMock::class);
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response);

        $handler = new CallableHandler($callable);

        $this->assertSame($response, $handler->handle($request));
    }
}
