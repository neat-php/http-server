<?php

namespace Neat\Http\Server;

use Generator;
use Neat\Http\Header\ContentDisposition;
use Neat\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

class Output
{
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    /** @var callable|null */
    private $viewRenderer;
    /** @var callable[] */
    private array $factories = [];

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?callable $viewRenderer = null
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->viewRenderer = $viewRenderer;

        $this->register(Response::class, function (Response $response) {
            return $response;
        });
    }

    /**
     * @param mixed $output
     * @return Generator<void, array-key, string, void>
     */
    private function types($output): iterable
    {
        if (is_object($output)) {
            yield get_class($output);
            yield from class_parents($output);
            yield from class_implements($output);
        }

        yield gettype($output);
    }

    public function register(string $type, callable $factory): void
    {
        $this->factories[$type] = $factory;
    }

    /**
     * @param mixed $output
     */
    public function resolve($output): Response
    {
        foreach ($this->types($output) as $type) {
            if ($factory = $this->factories[$type] ?? null) {
                return $factory($output);
            }
        }

        return $this->json($output);
    }

    public function body(string $content): Response
    {
        return $this
            ->response()
            ->withBody($this->streamFactory->createStream($content))
            ->withContentLength(strlen($content));
    }

    public function html(string $html): Response
    {
        return $this
            ->body($html)
            ->withContentType('text/html');
    }

    /**
     * @param mixed $data
     */
    public function json($data): Response
    {
        return $this
            ->body(json_encode($data))
            ->withContentType('application/json');
    }

    public function text(string $text): Response
    {
        return $this
            ->body($text)
            ->withContentType('text/plain');
    }

    public function xml(string $document): Response
    {
        return $this
            ->body($document)
            ->withContentType('text/xml');
    }

    public function view(string $template, array $data = []): Response
    {
        return $this->html(($this->viewRenderer)($template, $data));
    }

    /**
     * @param string|resource $file
     */
    private function file($file, string $disposition, ?string $filename = null, ?string $type = null): Response
    {
        if (is_string($file)) {
            $body = $this->streamFactory->createStreamFromFile($file);
        } elseif (is_resource($file)) {
            $body = $this->streamFactory->createStreamFromResource($file);
        } else {
            throw new RuntimeException('File must be a valid string or resource');
        }
        $size = $body->getSize();
        $response = $this
            ->response()
            ->withContentDisposition($disposition, $filename)
            ->withContentType($type)
            ->withBody($body);
        if ($size !== null) {
            $response = $response->withContentLength($size);
        }

        return $response;
    }

    /**
     * @param string|resource $file
     */
    public function download($file, ?string $filename = null, ?string $type = null): Response
    {
        return $this->file($file, ContentDisposition::ATTACHMENT, $filename, $type ?? 'application/octet-stream');
    }

    /**
     * @param string|resource $file
     */
    public function display($file, ?string $filename = null, ?string $type = null): Response
    {
        return $this->file($file, ContentDisposition::INLINE, $filename, $type ?? 'application/octet-stream');
    }

    public function response(int $code = 200, string $reason = ''): Response
    {
        return new Response($this->responseFactory->createResponse($code, $reason));
    }

    public function redirect(): Output\Redirect
    {
        return new Output\Redirect($this->responseFactory);
    }
}
