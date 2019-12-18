<?php

namespace Neat\Http\Server\Test\Middleware;

use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware\PsrMiddleware;
use Neat\Http\Server\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrMiddlewareTest extends TestCase
{
    public function testPsr()
    {
        $psr = $this->createMock(MiddlewareInterface::class);

        $middleware = new PsrMiddleware($psr);

        $this->assertSame($psr, $middleware->psr());
    }

    public function testProcessWithPsrHandler()
    {
        $psrRequest  = $this->createMock(ServerRequestInterface::class);
        $psrResponse = $this->createMock(ResponseInterface::class);
        $psrHandler  = $this->createMock(RequestHandlerInterface::class);

        $psr = $this->createMock(MiddlewareInterface::class);
        $psr
            ->expects($this->once())
            ->method('process')
            ->with($psrRequest, $psrHandler)
            ->willReturn($psrResponse);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('psr')
            ->willReturn($psrRequest);

        $handler = $this->createMock(Handler\PsrHandler::class);
        $handler
            ->expects($this->once())
            ->method('psr')
            ->willReturn($psrHandler);

        $middleware = new PsrMiddleware($psr);

        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($psrResponse, $response->psr());
    }

    public function testProcessWithPsrWrapper()
    {
        $psrRequest  = $this->createMock(ServerRequestInterface::class);
        $psrResponse = $this->createMock(ResponseInterface::class);

        $psr = $this->createMock(MiddlewareInterface::class);
        $psr
            ->expects($this->once())
            ->method('process')
            ->with($psrRequest, $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturn($psrResponse);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('psr')
            ->willReturn($psrRequest);

        $handler = $this->createMock(Handler\PsrHandler::class);

        $middleware = new PsrMiddleware($psr);

        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($psrResponse, $response->psr());
    }
}
