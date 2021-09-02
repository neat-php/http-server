<?php

namespace Neat\Http\Server;

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

    /** @var callable */
    private $headerReceiver;

    /** @var callable */
    private $headerTransmitter;

    /**
     * Server constructor
     *
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param StreamFactoryInterface        $streamFactory
     * @param UploadedFileFactoryInterface  $uploadedFileFactory
     * @param callable|null                 $headerReceiver
     * @param callable|null                 $headerTransmitter
     */
    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory,
        callable $headerReceiver = null,
        callable $headerTransmitter = null
    ) {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory        = $streamFactory;
        $this->uploadedFileFactory  = $uploadedFileFactory;
        $this->headerReceiver       = $headerReceiver ?? 'getallheaders';
        $this->headerTransmitter    = $headerTransmitter ?? 'header';
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
            }, array_combine(array_keys($files['name']), array_keys($files['name'])));
        }

        if ($multi) {
            return array_filter(array_map([$this, 'receiveUploadedFiles'], $files));
        }

        $stream = $files['error']
                ? $this->streamFactory->createStream('')
                : $this->streamFactory->createStreamFromFile($files['tmp_name']);

        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $files['size'],
            $files['error'],
            $files['name'],
            $files['type']
        );
    }

    /**
     * @return array
     */
    public function receiveHeaders(): array
    {
        return ($this->headerReceiver)();
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
     * @param array $server
     * @return string
     */
    public function receiveVersion($server): string
    {
        return str_replace('HTTP/', '', $server['SERVER_PROTOCOL'] ?? '1.1');
    }

    /**
     * @param array $server
     * @return string
     */
    public function receiveMethod($server): string
    {
        return $server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * @param array $server
     * @return string
     */
    public function receiveUri($server): string
    {
        $scheme  = ($server['HTTPS'] ?? null) === 'on' ? 'https' : 'http';
        $host    = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? null;
        $port    = $server['SERVER_PORT'] ?? null;
        $uri     = $server['REQUEST_URI'] ?? '/';
        if ($host && $port && strpos($host, ':') === false && $port != ($scheme == 'https' ? 443 : 80)) {
            $host .= ':' . $port;
        }

        return "$scheme://$host$uri";
    }

    /**
     * @return Request
     */
    public function receive(): Request
    {
        $serverRequest = $this->serverRequestFactory
            ->createServerRequest($this->receiveMethod($_SERVER), $this->receiveUri($_SERVER), $_SERVER)
            ->withProtocolVersion($this->receiveVersion($_SERVER))
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withBody($this->receiveBody())
            ->withUploadedFiles($this->receiveUploadedFiles($_FILES));

        foreach ($this->receiveHeaders() as $name => $value) {
            $serverRequest = $serverRequest->withHeader($name, $value);
        }

        return new Request($serverRequest);
    }

    /**
     * @param Response $response
     */
    public function send(Response $response)
    {
        $this->sendHeader($response->statusLine());
        $httpResponseCode = $response->status()->code();
        foreach ($response->headers() as $header) {
            $replace = $header->name() !== 'Set-Cookie';
            $this->sendHeader($header->line(), $replace, $httpResponseCode);
        }

        $this->sendBody($response->bodyStream());
    }

    /**
     * @param string   $line
     * @param bool     $replace
     * @param int|null $httpResponseCode
     */
    public function sendHeader(string $line, bool $replace = true, int $httpResponseCode = null)
    {
        ($this->headerTransmitter)($line, $replace, $httpResponseCode);
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
