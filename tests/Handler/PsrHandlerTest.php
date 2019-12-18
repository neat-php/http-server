<?php

namespace Neat\Http\Server\Test\Handler;

use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Handler\PsrHandler;
use Neat\Http\Server\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrHandlerTest extends TestCase
{
    public function testPsr()
    {
        $handler = new PsrHandler($psr = $this->createMock(RequestHandlerInterface::class));

        $this->assertInstanceOf(Handler::class, $handler);
        $this->assertSame($psr, $handler->psr());
    }

    public function testHandle()
    {
        $psrRequest  = $this->createMock(ServerRequestInterface::class);
        $psrResponse = $this->createMock(ResponseInterface::class);
        $psr         = $this->createMock(RequestHandlerInterface::class);
        $psr
            ->expects($this->once())
            ->method('handle')
            ->with($psrRequest)
            ->willReturn($psrResponse);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('psr')
            ->willReturn($psrRequest);

        $handler  = new PsrHandler($psr);
        $response = $handler->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($psrResponse, $response->psr());
    }
}
