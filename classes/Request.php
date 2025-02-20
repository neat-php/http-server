<?php

namespace Neat\Http\Server;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP Server Request
 */
class Request extends \Neat\Http\Request
{
    /** @var ServerRequestInterface */
    protected MessageInterface $message;

    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct($request);
    }

    public function psr(): ServerRequestInterface
    {
        return $this->message;
    }

    /**
     * Get query (aka GET) parameter(s)
     * @return mixed
     */
    public function query(?string $var = null)
    {
        if ($var === null) {
            return $this->message->getQueryParams();
        }

        return $this->message->getQueryParams()[$var] ?? null;
    }

    /**
     * Get parsed body (aka POST) parameter(s)
     * @return mixed
     */
    public function post(?string $var = null)
    {
        if ($var === null) {
            return $this->message->getParsedBody();
        }

        return $this->message->getParsedBody()[$var] ?? null;
    }

    /**
     * Get multipart files
     *
     * @param array $key
     * @return null|Upload|Upload[]|Upload[][]|...
     */
    public function files(...$key)
    {
        $files = $this->toFiles($this->message->getUploadedFiles());
        while ($key) {
            $files = $files[array_shift($key)] ?? null;
        }

        return $files;
    }

    /**
     * @param array $files
     * @return Upload|Upload[]|Upload[][]
     */
    protected function toFiles(array $files): array
    {
        return array_map(function ($file) {
            if (is_array($file)) {
                return $this->toFiles($file);
            }

            return new Upload($file);
        }, $files);
    }

    /**
     * Get cookie parameter(s)
     * @return mixed
     */
    public function cookie(?string $name = null)
    {
        if ($name === null) {
            return $this->message->getCookieParams();
        }

        return $this->message->getCookieParams()[$name] ?? null;
    }

    /**
     * @return mixed
     */
    public function server(?string $name = null)
    {
        if ($name === null) {
            return $this->message->getServerParams();
        }

        return $this->message->getServerParams()[$name] ?? null;
    }

    public function clientIp(): ?string
    {
        return $this->server('REMOTE_ADDR');
    }

    /**
     * @return static
     */
    public function withQuery(array $query)
    {
        $uri = $this->message->getUri()->withQuery(http_build_query($query));

        $new = clone $this;
        $new->message = $this->message->withUri($uri)->withQueryParams($query);

        return $new;
    }

    /**
     * @param Upload[]|Upload[][] $files
     * @return static
     */
    public function withFiles(array $files)
    {
        array_walk_recursive($files, function (&$upload) {
            /** @var Upload $upload */
            $upload = $upload->psr();
        });

        $new = clone $this;
        $new->message = $this->message->withUploadedFiles($files);

        return $new;
    }

    /**
     * @return static
     */
    public function withCookie(string $name, ?string $value)
    {
        $new = clone $this;
        if ($value !== null) {
            $new->message = $this->message->withCookieParams(array_merge($this->cookie(), [$name => $value]));
        } elseif ($this->cookie($name)) {
            $cookies = $this->cookie();
            unset($cookies[$name]);
            $new->message = $this->message->withCookieParams($cookies);
        }

        return $new;
    }
}
