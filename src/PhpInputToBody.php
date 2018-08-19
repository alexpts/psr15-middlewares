<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PhpInputToBody implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function process(ServerRequestInterface $request,  RequestHandlerInterface $handler) : ResponseInterface
    {
        $body = $this->parseBody($request);
        $parsedBody = $request->getParsedBody();

        $request = $request->withParsedBody(array_merge($body, $parsedBody));

        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function parseBody(ServerRequestInterface $request): array
    {
        $body = [];
        parse_str($request->getBody()->getContents(), $body);

        return $body;
    }
}
