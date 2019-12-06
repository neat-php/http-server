<?php

namespace Neat\Http\Server\Test\Handler;

use Neat\Http\Server\Handler;
use Neat\Http\Server\Handler\PsrHandler;
use PHPUnit\Framework\TestCase;
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
        $handler = new PsrHandler($psr = $this->createMock(RequestHandlerInterface::class));

        // TODO TEST HANDLE
    }
}
