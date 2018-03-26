<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Etag implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response = $handler->handle($request);

        return $this->addEtag($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function addEtag(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->canEtag($request, $response)) {
            $etag = 'W/"' . md5($response->getBody()->getContents()) . '"';
            $response = $response->withHeader('Etag', $etag);

            $clientEtag = $request->getHeader('If-None-Match');

            if ($clientEtag[0] === $etag) {
                $response = $response->withStatus(304);
            }

        }

        return $response;
    }

    protected function canEtag(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        return $request->getMethod() === 'GET'
            && $response->getStatusCode() === 200
            && $request->hasHeader('If-None-Match');
    }
}
