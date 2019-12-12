<?php

namespace Neat\Http\Server;

use Generator;
use Neat\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Output
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var callable[] */
    private $factories;

    /**
     * Responder constructor
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface   $streamFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;

        $this->register(Response::class, function (Response $response) {
            return $response;
        });
    }

    /**
     * @param $output
     * @return Generator|string[]
     */
    private function types($output)
    {
        if (is_object($output)) {
            yield get_class($output);
            yield from class_parents($output);
            yield from class_implements($output);
        }

        yield gettype($output);
    }

    /**
     * @param string   $type
     * @param callable $factory
     */
    public function register(string $type, callable $factory)
    {
        $this->factories[$type] = $factory;
    }

    /**
     * @param mixed $output
     * @return Response
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

    /**
     * @param string $html
     * @return Response
     */
    public function html(string $html): Response
    {
        return $this->response()
            ->withContentType('text/html')
            ->withBody($this->streamFactory->createStream($html));
    }

    /**
     * @param mixed $data
     * @return Response
     */
    public function json($data): Response
    {
        return $this->response()
            ->withContentType('application/json')
            ->withBody($this->streamFactory->createStream(json_encode($data)));
    }

    /**
     * @param string $text
     * @return Response
     */
    public function text(string $text): Response
    {
        return $this->response()
            ->withContentType('text/plain')
            ->withBody($this->streamFactory->createStream($text));
    }

    /**
     * @return Response
     */
    public function response(): Response
    {
        return new Response($this->responseFactory->createResponse());
    }
}
