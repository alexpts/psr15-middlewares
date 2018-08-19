<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StaticResponse implements MiddlewareInterface, RequestHandlerInterface
{
    /** @var ResponseInterface */
    protected $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return clone $this->response;
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $next
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next) : ResponseInterface
    {
        return $this->handle($request);
    }
}
