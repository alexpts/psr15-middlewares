<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use DomainException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use function in_array;
use function json_decode;

class JsonPayload implements MiddlewareInterface
{
    protected array $ignoreHttpMethods = ['GET', 'HEAD'];
    protected array $allowContentTypes = ['application/json'];

    protected int $decodeDepth = 512;
    protected int $decodeOptions = JSON_THROW_ON_ERROR;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     *
     * @return ResponseInterface
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws DomainException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $method = $request->getMethod();
        if (!in_array($method, $this->ignoreHttpMethods, true) && $this->isSupportContentType($request)) {
            $parsedJson = $this->parse($request);
            $request = $request->withParsedBody($parsedJson);
        }

        return $next->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     * @throws DomainException
     * @throws RuntimeException
     */
    protected function parse(ServerRequestInterface $request): array
    {
        $body = $request->getBody()->getContents();
        if ($body === '') {
            return [];
        }

        return json_decode($body, true, $this->decodeDepth, $this->decodeOptions);
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
            if (str_contains($contentType, $allowContentType)) {
                return true;
            }
        }

        return false;
    }
}
