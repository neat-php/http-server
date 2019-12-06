<?php

namespace Neat\Http\Server\Test;

use Neat\Http\Request;
use Neat\Http\Response;
use Neat\Http\Server\Dispatcher;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware;
use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase
{
    /**
     * Test handle without middleware
     */
    public function testHandleWithoutMiddleware()
    {
        $request  = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $handler  = $this->createMock(Handler::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($response);

        $dispatcher = new Dispatcher($handler);

        $this->assertSame($response, $dispatcher->handle($request));
    }

    /**
     * Test handle with immediate middleware interception
     */
    public function testHandleWithImmediateMiddlewareInterception()
    {
        $request  = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $middleware1 = $this->createMock(Middleware::class);
        $middleware1
            ->expects($this->once())
            ->method('process')
            ->with($request, $this->isInstanceOf(Handler::class))
            ->willReturn($response);

        $middleware2 = $this->createMock(Middleware::class);
        $middleware2
            ->expects($this->never())
            ->method('process');

        $handler = $this->createMock(Handler::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $dispatcher = new Dispatcher($handler, $middleware1, $middleware2);

        $this->assertSame($response, $dispatcher->handle($request));
    }

    /**
     * Test handle with middleware interception
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function testHandleWithMiddlewareInterception()
    {
        $requestA  = $this->createMock(Request::class);
        $requestB  = $this->createMock(Request::class);
        $responseA = $this->createMock(Response::class);
        $responseB = $this->createMock(Response::class);

        $middleware1 = $this->createMock(Middleware::class);
        $middleware1
            ->expects($this->once())
            ->method('process')
            ->with($requestA)
            ->will($this->returnCallback(function (Request $requestA, Handler $handler) use ($requestB, $responseA, $responseB) {
                $this->assertSame($responseB, $handler->handle($requestB));

                return $responseA;
            }));

        $middleware2 = $this->createMock(Middleware::class);
        $middleware2
            ->expects($this->once())
            ->method('process')
            ->with($requestB)
            ->willReturn($responseB);

        $handler = $this->createMock(Handler::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $dispatcher = new Dispatcher($handler, $middleware1, $middleware2);

        $this->assertSame($responseA, $dispatcher->handle($requestA));
    }

    /**
     * Test handle with middleware
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function testHandleWithMiddlewareHandling()
    {
        $requestA  = $this->createMock(Request::class);
        $requestB  = $this->createMock(Request::class);
        $requestC  = $this->createMock(Request::class);
        $responseA = $this->createMock(Response::class);
        $responseB = $this->createMock(Response::class);
        $responseC = $this->createMock(Response::class);

        $middleware1 = $this->createMock(Middleware::class);
        $middleware1
            ->expects($this->once())
            ->method('process')
            ->with($requestA)
            ->will($this->returnCallback(function (Request $requestA, Handler $handler) use ($requestB, $responseA, $responseB) {
                $this->assertSame($responseB, $handler->handle($requestB));

                return $responseA;
            }));

        $middleware2 = $this->createMock(Middleware::class);
        $middleware2
            ->expects($this->once())
            ->method('process')
            ->with($requestB)
            ->will($this->returnCallback(function (Request $requestA, Handler $handler) use ($requestC, $responseB, $responseC) {
                $this->assertSame($responseC, $handler->handle($requestC));

                return $responseB;
            }));

        $handler = $this->createMock(Handler::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($requestC)
            ->willReturn($responseC);

        $dispatcher = new Dispatcher($handler, $middleware1, $middleware2);

        $this->assertSame($responseA, $dispatcher->handle($requestA));
    }
}
