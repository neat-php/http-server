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
    private ServerRequestFactoryInterface $serverRequestFactory;
    private StreamFactoryInterface $streamFactory;
    private UploadedFileFactoryInterface $uploadedFileFactory;
    /** @var callable */
    private $headerReceiver;
    /** @var callable */
    private $headerTransmitter;

    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory,
        ?callable $headerReceiver = null,
        ?callable $headerTransmitter = null
    ) {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
        $this->headerReceiver = $headerReceiver ?? 'getallheaders';
        $this->headerTransmitter = $headerTransmitter ?? 'header';
    }

    /**
     * @return null|UploadedFileInterface|UploadedFileInterface[]|UploadedFileInterface[][]|...
     */
    public function receiveUploadedFiles(array $files)
    {
        $keys = array_keys($files);
        sort($keys);
        $multi = count(array_intersect(['error', 'name', 'size', 'tmp_name', 'type'], $keys)) !== 5;
        if (!$multi && is_array($files['name'])) {
            $multi = true;
            $files = array_map(function ($index) use ($files) {
                return array_combine(array_keys($files), array_column($files, $index));
            }, array_combine(array_keys($files['name']), array_keys($files['name'])));
        }

        if ($multi) {
            $normalized = [];
            foreach ($files as $key => $file) {
                if (!is_array($file)) {
                    continue;
                }
                $normalized[$key] = $this->receiveUploadedFiles($file);
            }
            return array_filter($normalized);
        }

        $stream = $files['error']
            ? $this->streamFactory->createStream('')
            : $this->streamFactory->createStreamFromFile($files['tmp_name']);

        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $files['size'],
            $files['error'],
            $files['name'],
            $files['type'],
        );
    }

    public function receiveHeaders(): array
    {
        return ($this->headerReceiver)();
    }

    public function receiveBody(): StreamInterface
    {
        $handle = defined('STDIN') ? constant('STDIN') : fopen('php://input', 'r+');

        return $this->streamFactory->createStreamFromResource($handle);
    }

    public function receiveVersion(array $server): string
    {
        return str_replace('HTTP/', '', $server['SERVER_PROTOCOL'] ?? '1.1');
    }

    public function receiveMethod(array $server): string
    {
        return $server['REQUEST_METHOD'] ?? 'GET';
    }

    public function receiveUri(array $server): string
    {
        $scheme = ($server['HTTPS'] ?? null) === 'on' ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? null;
        $port = $server['SERVER_PORT'] ?? null;
        $uri = $server['REQUEST_URI'] ?? '/';
        if ($host && $port && strpos($host, ':') === false && $port != ($scheme == 'https' ? 443 : 80)) {
            $host .= ':' . $port;
        }

        return "$scheme://$host$uri";
    }

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

    public function send(Response $response): void
    {
        $httpResponseCode = $response->status()->code();
        $this->sendHeader($response->statusLine(), true, $httpResponseCode);
        foreach ($response->headers() as $header) {
            $replace = $header->name() !== 'Set-Cookie';
            $this->sendHeader($header->line(), $replace, $httpResponseCode);
        }

        $this->sendBody($response->bodyStream());
    }

    public function sendHeader(string $line, bool $replace, int $httpResponseCode): void
    {
        ($this->headerTransmitter)($line, $replace, $httpResponseCode);
    }

    public function sendBody(StreamInterface $stream): void
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
