<?php

namespace Neat\Http\Server\Test;

use Neat\Http\Response;
use Neat\Http\Server\Output;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use stdClass;

class OutputTest extends TestCase
{
    public function testResolveResponse()
    {
        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $response = $this->createMock(Response::class);

        $this->assertSame($response, $responder->resolve($response));
    }

    public function testResolveClass()
    {
        $object = new stdClass();

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($object)->willReturn($response);

        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );
        $responder->register(stdClass::class, $factory);

        $this->assertSame($response, $responder->resolve($object));
    }

    public function testResolveInterface()
    {
        $psrResponse = $this->createMock(ResponseInterface::class);

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($psrResponse)->willReturn($response);

        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $responder->register(ResponseInterface::class, $factory);

        $this->assertSame($response, $responder->resolve($psrResponse));
    }

    public function testResolveObject()
    {
        $object = new stdClass();

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($object)->willReturn($response);

        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $responder->register('object', $factory);

        $this->assertSame($response, $responder->resolve($object));
    }

    public function testResolveType()
    {
        $string = 'testing';

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($string)->willReturn($response);

        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $responder->register('string', $factory);

        $this->assertSame($response, $responder->resolve($string));
    }

    public function testResolveDefault()
    {
        $response = $this->createMock(Response::class);
        $responder = $this->createPartialMock(Output::class, ['json']);
        $responder->expects($this->once())->method('json')->with([])->willReturn($response);

        $this->assertSame($response, $responder->resolve([]));
    }

    public function testJson()
    {
        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->once())->method('withHeader')->with('Content-Type', 'application/json')->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('{"key":"value"}')->willReturn($stream);

        $this->assertSame($response, $responder->json(['key' => 'value'])->psr());
    }

    public function testText()
    {
        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->once())->method('withHeader')->with('Content-Type', 'text/plain')->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('Hello world!')->willReturn($stream);

        $this->assertSame($response, $responder->text('Hello world!')->psr());
    }

    public function testHtml()
    {
        $responder = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->once())->method('withHeader')->with('Content-Type', 'text/html')->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('<html lang="en"><body>Hello world!</body></html>')->willReturn($stream);

        $this->assertSame($response, $responder->html('<html lang="en"><body>Hello world!</body></html>')->psr());
    }
}
