<?php

namespace Neat\Http\Server\Test\Middleware;

use Neat\Http\Request;
use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware\PsrMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;

class PsrMiddlewareTest extends TestCase
{
    public function testPsr()
    {
        $psr = $this->createMock(MiddlewareInterface::class);

        $middleware = new PsrMiddleware($psr);

        $this->assertSame($psr, $middleware->psr());
    }

    public function testProcess()
    {
        $request  = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $handler  = $this->createMock(Handler::class);

        $psr = $this->createMock(MiddlewareInterface::class);
        $psr->expects($this->once())->method('process')->with($request, $handler)->willReturn($response);

        $middleware = new PsrMiddleware($psr);

        $this->assertSame($response, $middleware->process($request, $handler));
    }
}
