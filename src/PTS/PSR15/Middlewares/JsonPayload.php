<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonPayload implements MiddlewareInterface
{
    /** @var array */
    protected $ignoreHttpMethods = ['GET', 'HEAD'];
    /** @var array */
    private $allowContentTypes = ['application/json'];

    /** @var int */
    protected $decodeDepth = 512;
    /** @var int */
    protected $decodeOptions = JSON_ERROR_NONE;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     *
     * @return ResponseInterface
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \DomainException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $method = $request->getMethod();
        if (!\in_array($method, $this->ignoreHttpMethods, true) && $this->isSupportContentType($request)) {
            $parsedJson = $this->parse($request);
            $request = $request->withParsedBody($parsedJson);
        }

        return $next->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     *
     * @throws \DomainException
     * @throws \RuntimeException
     */
    protected function parse(ServerRequestInterface $request): array
    {
        $body = $request->getBody()->getContents();
        if ($body === '') {
            return [];
        }

        $parsedJson = \json_decode($body, true, $this->decodeDepth, $this->decodeOptions);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DomainException(json_last_error_msg(), json_last_error());
        }

        return $parsedJson;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    protected function isSupportContentType(ServerRequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('content-type');

        foreach ($this->allowContentTypes as $allowContentType) {
            if (stripos($contentType, $allowContentType) === 0) {
                return true;
            }
        }

        return false;
    }
}
