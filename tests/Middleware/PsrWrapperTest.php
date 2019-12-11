<?php

namespace Neat\Http\Server\Test\Middleware;

use Neat\Http\Request;
use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware;
use Neat\Http\Server\Middleware\PsrWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrWrapperTest extends TestCase
{
    public function testProcess()
    {
        $psrRequest  = $this->createMock(ServerRequestInterface::class);
        $psrResponse = $this->createMock(ResponseInterface::class);
        $psrHandler  = $this->createMock(RequestHandlerInterface::class);

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('psr')
            ->willReturn($psrResponse);

        $middleware = $this->createMock(Middleware::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(Request::class), $this->isInstanceOf(Handler::class))
            ->willReturn($response);

        $psrWrapper = new PsrWrapper($middleware);
        $this->assertSame($psrResponse, $psrWrapper->process($psrRequest, $psrHandler));
    }
}
