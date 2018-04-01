<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ThrowableHandler implements MiddlewareInterface
{
    /** @var callable */
    protected $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        try {
            return $next->handle($request);
        } catch (\Throwable $throwable) {
            return \call_user_func($this->handler, $throwable, $request);
        }
    }
}
