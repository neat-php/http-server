<?php

namespace Neat\Http\Server\Output;

use Neat\Http\Request;
use Neat\Http\Response;
use Neat\Http\Server\Input;
use Neat\Http\Status;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;

/**
 * @method Response multipleChoices()   300 Multiple Choices [RFC7231, Section 6.4.1]
 * @method Response movedPermanently()  301 Moved Permanently [RFC7231, Section 6.4.2]
 * @method Response found()             302 Found [RFC7231, Section 6.4.3]
 * @method Response seeOther()          303 See Other [RFC7231, Section 6.4.4]
 * @method Response notModified()       304 Not Modified [RFC7232, Section 4.1]
 * @method Response useProxy()          305 Use Proxy [RFC7231, Section 6.4.5]
 * @method Response temporaryRedirect() 307 Temporary Redirect [RFC7231, Section 6.4.7]
 * @method Response permanentRedirect() 308 Permanent Redirect [RFC7538]
 * @see https://tools.ietf.org/html/rfc7231
 * @see https://tools.ietf.org/html/rfc7232
 * @see https://tools.ietf.org/html/rfc7538
 */
class Redirect
{
    public const CODES = [
        'multipleChoices' => 300,
        'movedPermanently' => 301,
        'found' => 302,
        'seeOther' => 303,
        'notModified' => 304,
        'useProxy' => 305,
        'temporaryRedirect' => 307,
        'permanentRedirect' => 308,
    ];

    private ResponseFactoryInterface $responseFactory;
    private bool $permanent = false;
    private bool $resubmit = false;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __call(string $name, array $arguments): Response
    {
        if ($code = self::CODES[$name] ?? null) {
            $response = $this->response($code);

            return $arguments ? $response->withHeader('Location', $arguments[0]) : $response;
        }

        throw new RuntimeException('Method does not exist');
    }

    /**
     * @return $this
     */
    public function permanent(bool $permanent = true): Redirect
    {
        $this->permanent = $permanent;

        return $this;
    }

    /**
     * @return $this
     */
    public function resubmit(bool $resubmit = true): Redirect
    {
        $this->resubmit = $resubmit;

        return $this;
    }

    public function code(): int
    {
        if ($this->resubmit) {
            return $this->permanent ? 308 : 307;
        } else {
            return $this->permanent ? 301 : 302;
        }
    }

    public function response(?int $code = null, ?string $reason = null): Response
    {
        $status = new Status($code ?? $this->code(), $reason);

        return new Response($this->responseFactory->createResponse($status->code(), $status->reason()));
    }

    public function to(string $url): Response
    {
        return $this->response()->withHeader('Location', $url);
    }

    public function back(Request $request, string $fallback = '/'): Response
    {
        $referer = $request->header('Referer');

        return $this->to($referer ? $referer->value() : $fallback);
    }

    public function refresh(Request $request): Response
    {
        return $this->to($request->url());
    }

    public function retry(Input $input): Response
    {
        $input->store();

        return $this->to($input->request()->url());
    }
}
