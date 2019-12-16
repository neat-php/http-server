<?php

namespace Neat\Http\Server\Test\Middleware;

use Neat\Http\Response;
use Neat\Http\ServerRequest;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware\CallableMiddleware;
use Neat\Http\Server\Test\CallableMock;
use PHPUnit\Framework\TestCase;

class CallableMiddlewareTest extends TestCase
{
    public function testCallable()
    {
        $callable = function () {
        };

        $middleware = new CallableMiddleware($callable);

        $this->assertSame($callable, $middleware->callable());
    }

    public function testProcess()
    {
        $request  = $this->createMock(ServerRequest::class);
        $response = $this->createMock(Response::class);
        $handler  = $this->createMock(Handler::class);

        $callable = $this->createMock(CallableMock::class);
        $callable->expects($this->once())->method('__invoke')->with($request, $handler)->willReturn($response);

        $middleware = new CallableMiddleware($callable);

        $this->assertSame($response, $middleware->process($request, $handler));
    }
}
