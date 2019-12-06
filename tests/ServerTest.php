<?php

namespace Neat\Http\Server\Test;

use Neat\Http\Server\Server;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class ServerTest extends TestCase
{
    const SUPERGLOBALS = ['_SERVER', '_GET', '_POST', '_COOKIE', '_FILES'];

    /**
     * Test capturing uploaded files from an empty set
     */
    public function testCaptureEmpty()
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
     * Test capturing a simple uploaded files
     */
    public function testReceiveUploadedFile()
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
     * Test capturing uploaded files from a multi dimensional files array
     */
    public function testCaptureMultiDimensional()
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
                            'name' => 'my-avatar.png',
                            'size' => 90996,
                            'type' => 'image/png',
                            'error' => 0,
                        ],
                    ],
                ],
            ]
        ));
    }

    /**
     * Test capturing multiple uploaded files from a non-normalized files array
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
        $file1 = $this->createMock(UploadedFileInterface::class);
        $file2 = $this->createMock(UploadedFileInterface::class);

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
                        'name' => [
                            0 => 'test1.txt',
                            1 => 'test2.txt',
                        ],
                        'size' => [
                            0 => 123,
                            1 => 123,
                        ],
                        'type' => [
                            0 => 'text/plain',
                            1 => 'text/plain',
                        ],
                        'error' => [
                            0 => 0,
                            1 => 0,
                        ],
                    ],
                ],
            ]
        ));
    }

    /**
     * Test capturing uploaded files from an invalid files array
     */
    public function testReceiveInvalid()
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

    public function withSuperGlobals($globals, callable $closure)
    {
        try {
            $backup = compact(self::SUPERGLOBALS);
            extract(array_intersect_key($globals, array_flip(self::SUPERGLOBALS)));
            $closure();
        } finally {
            extract($backup);
        }
    }

    public function testReceive()
    {
        $globals = [
            '_SERVER' => ['HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/'],
        ];

        $this->withSuperGlobals($globals, function () {
            $server = new Server(
                $this->createMock(ServerRequestFactoryInterface::class),
                $streamFactory = $this->createMock(StreamFactoryInterface::class),
                $this->createMock(UploadedFileFactoryInterface::class)
            );

            $stream = $this->createMock(StreamInterface::class);
            $streamFactory->expects($this->once())->method('createStreamFromResource')->with($this->isType('resource'))->willReturn($stream);

            $this->assertSame($stream, $server->receiveBody());
        });
    }
}
