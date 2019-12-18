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
    const CODES = [
        'multipleChoices'   => 300,
        'movedPermanently'  => 301,
        'found'             => 302,
        'seeOther'          => 303,
        'notModified'       => 304,
        'useProxy'          => 305,
        'temporaryRedirect' => 307,
        'permanentRedirect' => 308,
    ];

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var bool */
    private $permanent = false;

    /** @var bool */
    private $resubmit = false;

    /**
     * Redirect constructor
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return Response
     */
    public function __call(string $name, array $arguments)
    {
        if ($code = self::CODES[$name] ?? null) {
            $response = $this->response($code);

            return $arguments ? $response->withHeader('Location', $arguments[0]) : $response;
        }

        throw new RuntimeException('Method does not exist');
    }

    /**
     * @param bool $permanent
     * @return $this
     */
    public function permanent(bool $permanent = true): Redirect
    {
        $this->permanent = $permanent;

        return $this;
    }

    /**
     * @param bool $resubmit
     * @return $this
     */
    public function resubmit(bool $resubmit = true): Redirect
    {
        $this->resubmit = $resubmit;

        return $this;
    }

    /**
     * @return int
     */
    public function code(): int
    {
        if ($this->resubmit) {
            return $this->permanent ? 308 : 307;
        } else {
            return $this->permanent ? 301 : 302;
        }
    }

    /**
     * @param int    $code
     * @param string $reason
     * @return Response
     */
    public function response(int $code = null, string $reason = null): Response
    {
        $status = new Status($code ?? $this->code(), $reason);

        return new Response($this->responseFactory->createResponse($status->code(), $status->reason()));
    }

    /**
     * @param string $url
     * @return Response
     */
    public function to(string $url): Response
    {
        return $this->response()->withHeader('Location', $url);
    }

    /**
     * @param Request $request
     * @param string  $fallback
     * @return Response
     */
    public function back(Request $request, string $fallback = '/'): Response
    {
        $referer = $request->header('Referer');

        return $this->to($referer ? $referer->value() : $fallback);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function refresh(Request $request): Response
    {
        return $this->to($request->url());
    }

    /**
     * @param Input $input
     * @return Response
     */
    public function retry(Input $input): Response
    {
        $input->store();

        return $this->to($input->request()->url());
    }
}
