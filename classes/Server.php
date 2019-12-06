<?php

namespace Neat\Http\Server;

use Neat\Http\Request;
use Neat\Http\Response;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class Server
{
    /** @var ServerRequestFactoryInterface */
    private $serverRequestFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var UploadedFileFactoryInterface */
    private $uploadedFileFactory;

    /**
     * Server constructor
     *
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param StreamFactoryInterface        $streamFactory
     * @param UploadedFileFactoryInterface  $uploadedFileFactory
     */
    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory
    ) {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory        = $streamFactory;
        $this->uploadedFileFactory  = $uploadedFileFactory;
    }

    /**
     * @param array $files
     * @return null|UploadedFileInterface|UploadedFileInterface[]|UploadedFileInterface[][]|...
     */
    public function receiveUploadedFiles($files)
    {
        if (!is_array($files)) {
            return null;
        }

        $keys = array_keys($files);
        sort($keys);
        $multi = $keys !== ['error', 'name', 'size', 'tmp_name', 'type'];
        if (!$multi && is_array($files['name'])) {
            $multi = true;
            $files = array_map(function ($index) use ($files) {
                return array_combine(array_keys($files), array_column($files, $index));
            }, array_keys($files['name']));
        }

        if ($multi) {
            return array_filter(array_map([$this, 'receiveUploadedFiles'], $files));
        }

        return $this->uploadedFileFactory->createUploadedFile(
            $this->streamFactory->createStreamFromFile($files['tmp_name']),
            $files['size'],
            $files['error'],
            $files['name'],
            $files['type']
        );
    }

    /**
     * @return StreamInterface
     */
    public function receiveBody(): StreamInterface
    {
        $handle = defined('STDIN') ? constant('STDIN') : fopen('php://input', 'r+');

        return $this->streamFactory->createStreamFromResource($handle);
    }

    /**
     * @return Request
     */
    public function receive(): Request
    {
        $version = str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL'] ?? '1.1');
        $method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $scheme  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $uri     = "$scheme://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $serverRequest = $this->serverRequestFactory
            ->createServerRequest($method, $uri, $_SERVER)
            ->withProtocolVersion($version)
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withBody($this->receiveBody())
            ->withUploadedFiles($this->receiveUploadedFiles($_FILES));

        foreach (getallheaders() as $name => $value) {
            $serverRequest = $serverRequest->withHeader($name, $value);
        }

        return new Request($serverRequest);
    }

    /**
     * @param Response $response
     */
    public function send(Response $response)
    {
        header($response->statusLine());
        foreach ($response->headers() as $header) {
            header($header->line());
        }

        $this->sendBody($response->bodyStream());
    }

    /**
     * @param StreamInterface $stream
     */
    public function sendBody(StreamInterface $stream)
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        if (!$stream->isReadable()) {
            echo $stream;

            return;
        }
        while (!$stream->eof()) {
            echo $stream->read(1024);
        }
    }
}
