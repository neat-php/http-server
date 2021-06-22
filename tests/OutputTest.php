<?php

namespace Neat\Http\Server\Test;

use Neat\Http\Response;
use Neat\Http\Server\Output;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use stdClass;

class OutputTest extends TestCase
{
    public function testResolveResponse()
    {
        $output = new Output(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );

        $response = $this->createMock(Response::class);

        $this->assertSame($response, $output->resolve($response));
    }

    public function testResolveClass()
    {
        $object = new stdClass();

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($object)->willReturn($response);

        $output = new Output(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );
        $output->register(stdClass::class, $factory);

        $this->assertSame($response, $output->resolve($object));
    }

    public function testResolveInterface()
    {
        $psrResponse = $this->createMock(ResponseInterface::class);

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($psrResponse)->willReturn($response);

        $output = new Output(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );

        $output->register(ResponseInterface::class, $factory);

        $this->assertSame($response, $output->resolve($psrResponse));
    }

    public function testResolveObject()
    {
        $object = new stdClass();

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($object)->willReturn($response);

        $output = new Output(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );

        $output->register('object', $factory);

        $this->assertSame($response, $output->resolve($object));
    }

    public function testResolveType()
    {
        $string = 'testing';

        $response = $this->createMock(Response::class);

        $factory = $this->createMock(CallableMock::class);
        $factory->expects($this->once())->method('__invoke')->with($string)->willReturn($response);

        $output = new Output(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );

        $output->register('string', $factory);

        $this->assertSame($response, $output->resolve($string));
    }

    public function testResolveDefault()
    {
        $response = $this->createMock(Response::class);
        $responder = $this->createPartialMock(Output::class, ['json']);
        $responder->expects($this->once())->method('json')->with([])->willReturn($response);

        $this->assertSame($response, $responder->resolve([]));
    }

    public function testBody()
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->once())->method('withHeader')->with('Content-Length', ['7'])->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('CONTENT')->willReturn($stream);

        $this->assertSame($response, $output->body('CONTENT')->psr());
    }

    public function testJson()
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->exactly(2))->method('withHeader')->withConsecutive(['Content-Length', ['15']], ['Content-Type', ['application/json']])->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('{"key":"value"}')->willReturn($stream);

        $this->assertSame($response, $output->json(['key' => 'value'])->psr());
    }

    public function testText()
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->exactly(2))->method('withHeader')->withConsecutive(['Content-Length', ['12']], ['Content-Type', ['text/plain']])->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('Hello world!')->willReturn($stream);

        $this->assertSame($response, $output->text('Hello world!')->psr());
    }

    public function testHtml()
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->exactly(2))->method('withHeader')->withConsecutive(['Content-Length', ['48']], ['Content-Type', ['text/html']])->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('<html lang="en"><body>Hello world!</body></html>')->willReturn($stream);

        $this->assertSame($response, $output->html('<html lang="en"><body>Hello world!</body></html>')->psr());
    }

    public function testXml()
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->exactly(2))->method('withHeader')->withConsecutive(['Content-Length', ['66']], ['Content-Type', ['text/xml']])->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $streamFactory->expects($this->once())->method('createStream')->with('<?xml version="1.0"?><book><title>This is the title</title></book>')->willReturn($stream);

        $this->assertSame($response, $output->xml('<?xml version="1.0"?><book><title>This is the title</title></book>')->psr());
    }

    public function testView()
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $viewRenderer = $this->createMock(CallableMock::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $streamFactory->expects($this->once())->method('createStream')->with('<h1>Hello world!</h1>')->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->exactly(2))->method('withHeader')->withConsecutive(['Content-Length', ['21']], ['Content-Type', ['text/html']])->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);


        $viewRenderer
            ->expects($this->once())
            ->method('__invoke')
            ->with('view/template', ['message' => 'Hello world!'])
            ->willReturn('<h1>Hello world!</h1>');

        $this->assertSame($response, $output->view('view/template', ['message' => 'Hello world!'])->psr());
    }

    public function provideDisposition(): array
    {
        return [
            ['download', 'attachment'],
            ['display', 'inline'],
        ];
    }

    /**
     * @dataProvider provideDisposition
     * @param string $method
     * @param string $disposition
     */
    public function testFileResource(string $method, string $disposition)
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $resource = fopen('php://temp', 'r+');
        $stream   = $this->createMock(StreamInterface::class);

        $streamFactory->expects($this->once())->method('createStreamFromResource')->with($resource)->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->at(0))->method('withHeader')->with('Content-Disposition', [$disposition])->willReturnSelf();
        $response->expects($this->at(1))->method('withHeader')->with('Content-Type',  ['application/octet-stream'])->willReturnSelf();
        $response->expects($this->at(2))->method('withBody')->with($stream)->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $this->assertSame($response, $output->$method($resource)->psr());
    }

    /**
     * @dataProvider provideDisposition
     * @param string $method
     * @param string $disposition
     */
    public function testFilePath(string $method, string $disposition)
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $file   = '/path/to/file';
        $stream = $this->createMock(StreamInterface::class);

        $streamFactory->expects($this->once())->method('createStreamFromFile')->with($file)->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->at(0))->method('withHeader')->with('Content-Disposition', [$disposition])->willReturnSelf();
        $response->expects($this->at(1))->method('withHeader')->with('Content-Type',  ['application/octet-stream'])->willReturnSelf();
        $response->expects($this->at(2))->method('withBody')->with($stream)->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $this->assertSame($response, $output->$method($file)->psr());
    }

    /**
     * @dataProvider provideDisposition
     * @param string $method
     * @param string $disposition
     */
    public function testWithLength(string $method, string $disposition)
    {
        $size = rand(0, 1000);
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $file   = '/path/to/file';
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('getSize')->willReturn($size);

        $streamFactory->expects($this->once())->method('createStreamFromFile')->with($file)->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->at(0))->method('withHeader')->with('Content-Disposition', [$disposition])->willReturnSelf();
        $response->expects($this->at(1))->method('withHeader')->with('Content-Type',  ['application/octet-stream'])->willReturnSelf();
        $response->expects($this->at(2))->method('withBody')->with($stream)->willReturnSelf();
        $response->expects($this->at(3))->method('withHeader')->with('Content-Length', [$size])->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $this->assertSame($response, $output->$method($file)->psr());
    }

    /**
     * @dataProvider provideDisposition
     * @param string $method
     * @param string $disposition
     */
    public function testWithOutLength(string $method, string $disposition)
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class)
        );

        $file   = '/path/to/file';
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('getSize')->willReturn(null);

        $streamFactory->expects($this->once())->method('createStreamFromFile')->with($file)->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->at(0))->method('withHeader')->with('Content-Disposition', [$disposition])->willReturnSelf();
        $response->expects($this->at(1))->method('withHeader')->with('Content-Type',  ['application/octet-stream'])->willReturnSelf();
        $response->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();

        $responseFactory->expects($this->once())->method('createResponse')->with()->willReturn($response);

        $this->assertSame($response, $output->$method($file)->psr());
    }

    /**
     * @dataProvider provideDisposition
     * @param string $method
     */
    public function testFileInvalid(string $method)
    {
        $this->expectExceptionObject(new RuntimeException('File must be a valid string or resource'));

        $output = new Output(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );

        $output->$method(null);
    }

    public function testResponse()
    {
        $output = new Output(
            $responseFactory = $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );

        $response = $this->createMock(ResponseInterface::class);

        $responseFactory->expects($this->once())->method('createResponse')->with(404, 'Not found')->willReturn($response);

        $this->assertSame($response, $output->response(404, 'Not found')->psr());
    }
}
