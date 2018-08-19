<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResponseTime implements MiddlewareInterface
{
    protected const HEADER = 'X-Response-Time';

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        return $this->process($request, $next);
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
        $server = $request->getServerParams();
        $startTime = $server['REQUEST_TIME_FLOAT'] ?? microtime(true);

        $response = $next->handle($request);
        $diff = $this->getDiff($startTime);

        return $response->withHeader(self::HEADER, sprintf('%2.3fms', $diff));
    }

    protected function getDiff(float $startTime): float
    {
        return (microtime(true) - $startTime) * 1000;
    }
}
