<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use InvalidArgumentException;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Etag implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
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
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function addEtag(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->canEtag($request, $response)) {
            $streamData = $response->getBody()->getContents();
            $etag = 'W/"' . md5($streamData) . '"';
            $response = $response->withHeader('Etag', $etag);
            $response = $this->setNotModifyHeader($request, $response, $etag);
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $etag
     *
     * @return ResponseInterface
     */
    protected function setNotModifyHeader(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $etag
    ): ResponseInterface {
        $clientEtag = $request->getHeaderLine('If-None-Match');

        if ($clientEtag === $etag) {
            $response = $response->withStatus(304);
            $response->getBody()->close();
            $emptyStream = new Stream('php://memory', 'wb+');
            $response = $response->withBody($emptyStream);
        }

        return $response;
    }

    protected function canEtag(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        return $request->getMethod() === 'GET'
            && $response->getStatusCode() === 200;
    }
}
