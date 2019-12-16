<?php

namespace Neat\Http\Server\Test\Handler;

use Neat\Http\Response;
use Neat\Http\ServerRequest;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Handler\PsrWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class PsrWrapperTest extends TestCase
{
    public function testHandle()
    {
        $psrRequest  = $this->createMock(ServerRequestInterface::class);
        $psrResponse = $this->createMock(ResponseInterface::class);

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('psr')
            ->willReturn($psrResponse);

        $handler = $this->createMock(Handler::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(ServerRequest::class))
            ->willReturn($response);

        $psrWrapper = new PsrWrapper($handler);
        $this->assertSame($psrResponse, $psrWrapper->handle($psrRequest));
    }
}
