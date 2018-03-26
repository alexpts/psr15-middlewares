<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IsXhr implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $isXHR = $this->hasXHR($request);
        return $handler->handle($request->withAttribute('xhr', $isXHR));
    }

    protected function hasXHR(ServerRequestInterface $request): bool
    {
        return $request->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
}
