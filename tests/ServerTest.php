<?php

namespace Neat\Http\Server\Test;

use Neat\Http\Header;
use Neat\Http\Response;
use Neat\Http\Server\Request;
use Neat\Http\Server\Server;
use Neat\Http\Status;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class ServerTest extends TestCase
{
    /**
     * Test receiving uploaded files from an empty set
     */
    public function testReceiveEmptyFiles()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $uploadedFileFactory = $this->createMock(UploadedFileFactoryInterface::class)
        );

        $this->assertSame([], $server->receiveUploadedFiles([]));
        $this->assertSame([], $server->receiveUploadedFiles(['empty' => []]));
    }

    /**
     * Test receiving a single uploaded file
     */
    public function testReceiveSingleFile()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $uploadedFileFactory = $this->createMock(UploadedFileFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);
        $avatar = $this->createMock(UploadedFileInterface::class);

        $streamFactory->expects($this->once())->method('createStreamFromFile')->with(__DIR__ . '/test.txt')->willReturn($stream);
        $uploadedFileFactory->expects($this->once())->method('createUploadedFile')->with($stream, 90996, 0, 'my-avatar.png', 'image/png')->willReturn($avatar);

        $this->assertEquals(['avatar' => $avatar], $server->receiveUploadedFiles(
            [
                'avatar' => [
                    'tmp_name' => __DIR__ . '/test.txt',
                    'name'     => 'my-avatar.png',
                    'size'     => 90996,
                    'type'     => 'image/png',
                    'error'    => 0,
                ],
            ]
        ));
    }

    /**
     * Test receiving uploaded files from a multi dimensional files array
     */
    public function testReceiveMultiDimensionalFiles()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $uploadedFileFactory = $this->createMock(UploadedFileFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);
        $avatar = $this->createMock(UploadedFileInterface::class);

        $streamFactory->expects($this->once())->method('createStreamFromFile')->with(__DIR__ . '/test.txt')->willReturn($stream);
        $uploadedFileFactory->expects($this->once())->method('createUploadedFile')->with($stream, 90996, 0, 'my-avatar.png', 'image/png')->willReturn($avatar);

        $this->assertEquals(['my-form' => ['details' => ['avatar' => $avatar]]], $server->receiveUploadedFiles(
            [
                'my-form' => [
                    'details' => [
                        'avatar' => [
                            'tmp_name' => __DIR__ . '/test.txt',
                            'name'     => 'my-avatar.png',
                            'size'     => 90996,
                            'type'     => 'image/png',
                            'error'    => 0,
                        ],
                    ],
                ],
            ]
        ));
    }

    public function testReceiveAlternateFilesArray()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $uploadedFileFactory = $this->createMock(UploadedFileFactoryInterface::class)
        );

        $stream1 = $this->createMock(StreamInterface::class);
        $file1   = $this->createMock(UploadedFileInterface::class);
        $streamFactory->expects($this->at(0))
            ->method('createStreamFromFile')
            ->with(__DIR__ . '/test1.txt')
            ->willReturn($stream1);

        $uploadedFileFactory->expects($this->at(0))
            ->method('createUploadedFile')
            ->with($stream1, 123, 0, 'test1.txt', 'text/plain')
            ->willReturn($file1);

        $expected = ['item' => ['1' => ['img' => $file1]]];
        $files    = [
            'item' => [
                'tmp_name' => ['1' => ['img' => __DIR__ . '/test1.txt']],
                'name'     => ['1' => ['img' => 'test1.txt']],
                'size'     => ['1' => ['img' => 123]],
                'type'     => ['1' => ['img' => 'text/plain']],
                'error'    => ['1' => ['img' => 0]],
            ],
        ];
        $this->assertEquals($expected, $server->receiveUploadedFiles($files));
    }

    /**
     * Test receiving multiple uploaded files from a non-normalized files array
     */
    public function testReceiveNormalized()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $uploadedFileFactory = $this->createMock(UploadedFileFactoryInterface::class)
        );

        $stream1 = $this->createMock(StreamInterface::class);
        $stream2 = $this->createMock(StreamInterface::class);
        $file1   = $this->createMock(UploadedFileInterface::class);
        $file2   = $this->createMock(UploadedFileInterface::class);

        $streamFactory->expects($this->at(0))->method('createStreamFromFile')->with(__DIR__ . '/test1.txt')->willReturn($stream1);
        $streamFactory->expects($this->at(1))->method('createStreamFromFile')->with(__DIR__ . '/test2.txt')->willReturn($stream2);
        $uploadedFileFactory->expects($this->at(0))->method('createUploadedFile')->with($stream1, 123, 0, 'test1.txt', 'text/plain')->willReturn($file1);
        $uploadedFileFactory->expects($this->at(1))->method('createUploadedFile')->with($stream1, 123, 0, 'test2.txt', 'text/plain')->willReturn($file2);

        $this->assertEquals(['my-form' => ['details' => [$file1, $file2]]], $server->receiveUploadedFiles(
            [
                'my-form' => [
                    'details' => [
                        'tmp_name' => [
                            0 => __DIR__ . '/test1.txt',
                            1 => __DIR__ . '/test2.txt',
                        ],
                        'name'     => [
                            0 => 'test1.txt',
                            1 => 'test2.txt',
                        ],
                        'size'     => [
                            0 => 123,
                            1 => 123,
                        ],
                        'type'     => [
                            0 => 'text/plain',
                            1 => 'text/plain',
                        ],
                        'error'    => [
                            0 => 0,
                            1 => 0,
                        ],
                    ],
                ],
            ]
        ));
    }

    /**
     * Test receiving uploaded files from an invalid files array
     */
    public function testReceiveInvalidFile()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        $this->assertNull($server->receiveUploadedFiles(null));
        $this->assertSame([], $server->receiveUploadedFiles(['avatar' => 1]));
    }

    /**
     * Test receiving uploaded files from an invalid files array
     */
    public function testReceiveNoFile()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $uploadedFileFactory = $this->createMock(UploadedFileFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);
        $upload = $this->createMock(UploadedFileInterface::class);

        $streamFactory->expects($this->once())->method('createStream')->with('')->willReturn($stream);
        $uploadedFileFactory->expects($this->once())->method('createUploadedFile')->with($stream, 0, 4, '', '')->willReturn($upload);

        $this->assertNull($server->receiveUploadedFiles(null));
        $this->assertSame(['empty' => $upload], $server->receiveUploadedFiles([
            'empty' => [
                'name'     => '',
                'type'     => '',
                'size'     => 0,
                'tmp_name' => '',
                'error'    => UPLOAD_ERR_NO_FILE,
            ],
        ]));
    }

    /**
     * Test receive body
     */
    public function testReceiveBody()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        $stream = $this->createMock(StreamInterface::class);
        $streamFactory->expects($this->once())->method('createStreamFromResource')->with($this->isType('resource'))->willReturn($stream);

        $this->assertSame($stream, $server->receiveBody());
    }

    /**
     * Test receive method
     */
    public function testReceiveMethod()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        $this->assertSame('GET', $server->receiveMethod([]));
        $this->assertSame('GET', $server->receiveMethod(['REQUEST_METHOD' => 'GET']));
        $this->assertSame('POST', $server->receiveMethod(['REQUEST_METHOD' => 'POST']));
    }

    /**
     * Test receive method
     */
    public function testReceiveVersion()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        $this->assertSame('1.1', $server->receiveVersion([]));
        $this->assertSame('1.1', $server->receiveVersion(['SERVER_PROTOCOL' => 'HTTP/1.1']));
        $this->assertSame('2.0', $server->receiveVersion(['SERVER_PROTOCOL' => 'HTTP/2.0']));
    }

    /**
     * Test receive method
     */
    public function testReceiveUri()
    {
        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        $this->assertSame('http://example.com/', $server->receiveUri(['HTTP_HOST' => 'example.com']));
        $this->assertSame('http://example.com/', $server->receiveUri(['SERVER_NAME' => 'example.com']));
        $this->assertSame('http://example.com/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'SERVER_PORT' => 80]));
        $this->assertSame('http://example.com:8080/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'SERVER_PORT' => 8080]));

        $this->assertSame('http://example.com/', $server->receiveUri(['HTTP_HOST' => 'example.com', 'HTTPS' => 'off']));
        $this->assertSame('https://example.com/', $server->receiveUri(['HTTP_HOST' => 'example.com', 'HTTPS' => 'on']));
        $this->assertSame('https://example.com/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'HTTPS' => 'on']));
        $this->assertSame('https://example.com/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'HTTPS' => 'on', 'SERVER_PORT' => 443]));
        $this->assertSame('https://example.com:4433/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'HTTPS' => 'on', 'SERVER_PORT' => 4433]));

        $this->assertSame('https://example.com/', $server->receiveUri(['HTTP_HOST' => 'example.com', 'HTTPS' => 'on']));
        $this->assertSame('https://example.com/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'HTTPS' => 'on']));
        $this->assertSame('https://example.com/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'HTTPS' => 'on', 'SERVER_PORT' => 443]));
        $this->assertSame('https://example.com:4433/', $server->receiveUri(['SERVER_NAME' => 'example.com', 'HTTPS' => 'on', 'SERVER_PORT' => 4433]));

        $this->assertSame('http://example.com/', $server->receiveUri(['HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/']));
        $this->assertSame('http://example.com/page?query=string', $server->receiveUri(['HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/page?query=string']));
    }

    /**
     * Test receive
     *
     * @backupGlobals enabled
     */
    public function testReceiveGetRequest()
    {
        $_SERVER = ['HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/'];
        $_GET    = [];
        $_POST   = [];
        $_COOKIE = [];
        $_FILES  = [];

        $getAllHeadersMock = $this->createMock(CallableMock::class);
        $getAllHeadersMock->expects($this->once())->method('__invoke')->willReturn([]);

        $server = new Server(
            $serverRequestFactory = $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class),
            $getAllHeadersMock
        );

        $stream = $this->createMock(StreamInterface::class);
        $streamFactory->expects($this->once())->method('createStreamFromResource')->with($this->isType('resource'))->willReturn($stream);

        $psrRequest = $this->createMock(ServerRequestInterface::class);
        $psrRequest->expects($this->once())->method('withProtocolVersion')->with('1.1')->willReturnSelf();
        $psrRequest->expects($this->once())->method('withCookieParams')->with([])->willReturnSelf();
        $psrRequest->expects($this->once())->method('withQueryParams')->with([])->willReturnSelf();
        $psrRequest->expects($this->once())->method('withParsedBody')->with([])->willReturnSelf();
        $psrRequest->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $psrRequest->expects($this->once())->method('withUploadedFiles')->with([])->willReturnSelf();

        $serverRequestFactory->expects($this->once())->method('createServerRequest')->with('GET', 'http://localhost/', $_SERVER)->willReturn($psrRequest);

        $request = $server->receive();

        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame($psrRequest, $request->psr());
    }

    /**
     * Test receive
     *
     * @backupGlobals enabled
     */
    public function testReceivePostRequest()
    {
        $_SERVER = [
            'HTTPS'           => 'on',
            'HTTP_HOST'       => 'example.com',
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/test?query=query-value',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ];
        $_GET    = ['query' => 'query-value'];
        $_POST   = ['body' => 'body-value'];
        $_COOKIE = ['cookie' => 'cookie-value'];
        $_FILES  = [
            'avatar' => [
                'tmp_name' => __DIR__ . '/test.txt',
                'name'     => 'my-avatar.png',
                'size'     => 90996,
                'type'     => 'image/png',
                'error'    => 0,
            ],
        ];

        $getAllHeadersMock = $this->createMock(CallableMock::class);
        $getAllHeadersMock->expects($this->once())->method('__invoke')->willReturn(['Content-Type' => 'application/json; charset=utf8']);

        $server = new Server(
            $serverRequestFactory = $this->createMock(ServerRequestFactoryInterface::class),
            $streamFactory = $this->createMock(StreamFactoryInterface::class),
            $uploadedFileFactory = $this->createMock(UploadedFileFactoryInterface::class),
            $getAllHeadersMock
        );

        $stream = $this->createMock(StreamInterface::class);
        $avatar = $this->createMock(UploadedFileInterface::class);

        $streamFactory->expects($this->once())->method('createStreamFromFile')->with(__DIR__ . '/test.txt')->willReturn($stream);
        $uploadedFileFactory->expects($this->once())->method('createUploadedFile')->with($stream, 90996, 0, 'my-avatar.png', 'image/png')->willReturn($avatar);

        $stream = $this->createMock(StreamInterface::class);
        $streamFactory->expects($this->once())->method('createStreamFromResource')->with($this->isType('resource'))->willReturn($stream);

        $psrRequest = $this->createMock(ServerRequestInterface::class);
        $psrRequest->expects($this->once())->method('withProtocolVersion')->with('1.0')->willReturnSelf();
        $psrRequest->expects($this->once())->method('withCookieParams')->with($_COOKIE)->willReturnSelf();
        $psrRequest->expects($this->once())->method('withQueryParams')->with($_GET)->willReturnSelf();
        $psrRequest->expects($this->once())->method('withParsedBody')->with($_POST)->willReturnSelf();
        $psrRequest->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();
        $psrRequest->expects($this->once())->method('withUploadedFiles')->with(['avatar' => $avatar])->willReturnSelf();
        $psrRequest->expects($this->once())->method('withHeader')->with('Content-Type', 'application/json; charset=utf8')->willReturnSelf();

        $serverRequestFactory->expects($this->once())->method('createServerRequest')->with('POST', 'https://example.com/test?query=query-value', $_SERVER)->willReturn($psrRequest);

        $request = $server->receive();

        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame($psrRequest, $request->psr());
    }

    public function testSendBody()
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())->method('isSeekable')->willReturn(true);
        $body->expects($this->once())->method('rewind');
        $body->expects($this->once())->method('isReadable')->willReturn(true);
        $body->expects($this->exactly(2))->method('eof')->willReturnOnConsecutiveCalls(false, true);
        $body->expects($this->once())->method('read')->with(1024)->willReturn('testing');

        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        ob_start();
        $server->sendBody($body);
        $this->assertSame('testing', ob_get_clean());
    }

    public function testSendUnreadableBody()
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())->method('isSeekable')->willReturn(true);
        $body->expects($this->once())->method('rewind');
        $body->expects($this->once())->method('isReadable')->willReturn(false);
        $body->expects($this->once())->method('__toString')->willReturn('testing');

        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        ob_start();
        $server->sendBody($body);
        $this->assertSame('testing', ob_get_clean());
    }

    public function testSendNotSeekableUnreadableBody()
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())->method('isSeekable')->willReturn(false);
        $body->expects($this->once())->method('isReadable')->willReturn(false);
        $body->expects($this->once())->method('__toString')->willReturn('testing');

        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class)
        );

        ob_start();
        $server->sendBody($body);
        $this->assertSame('testing', ob_get_clean());
    }

    public function testSendHeader()
    {
        $transmitter = $this->createMock(CallableMock::class);
        $transmitter->expects($this->once())->method('__invoke')->with('X-Test: value');

        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class),
            null,
            $transmitter
        );

        $server->sendHeader('X-Test: value');
    }

    public function testSendResponse()
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())->method('isSeekable')->willReturn(false);
        $body->expects($this->once())->method('isReadable')->willReturn(false);
        $body->expects($this->once())->method('__toString')->willReturn('Hello world!');

        $contentType = $this->createMock(Header::class);
        $contentType->expects($this->once())->method('name')->willReturn('Content-Type');
        $contentType->expects($this->once())->method('line')->willReturn('Content-Type: text/html');
        $setCookie = $this->createMock(Header::class);
        $setCookie->expects($this->once())->method('name')->willReturn('Set-Cookie');
        $setCookie->expects($this->once())->method('line')->willReturn('Set-Cookie: test');
        $setCookie2 = $this->createMock(Header::class);
        $setCookie2->expects($this->once())->method('name')->willReturn('Set-Cookie');
        $setCookie2->expects($this->once())->method('line')->willReturn('Set-Cookie: test2');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('statusLine')->willReturn('HTTP/1.1 200 OK');
        $response->expects($this->once())->method('status')->willReturn(new Status(200));
        $response->expects($this->once())->method('headers')->willReturn([$contentType, $setCookie, $setCookie2]);
        $response->expects($this->once())->method('bodyStream')->willReturn($body);

        $transmitter = $this->createMock(CallableMock::class);
        $transmitter->expects($this->at(0))->method('__invoke')->with('HTTP/1.1 200 OK');
        $transmitter->expects($this->at(1))->method('__invoke')->with('Content-Type: text/html', true, 200);
        $transmitter->expects($this->at(2))->method('__invoke')->with('Set-Cookie: test', false, 200);
        $transmitter->expects($this->at(3))->method('__invoke')->with('Set-Cookie: test2', false, 200);

        $server = new Server(
            $this->createMock(ServerRequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(UploadedFileFactoryInterface::class),
            null,
            $transmitter
        );

        ob_start();
        $server->send($response);
        $this->assertSame('Hello world!', ob_get_clean());
    }
}
