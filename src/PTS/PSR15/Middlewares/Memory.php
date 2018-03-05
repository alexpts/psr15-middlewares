<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Memory implements MiddlewareInterface
{
    protected const HEADER = 'X-Memory';

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
        $response = $next->handle($request);
        $memory = $this->getPeakMemory() . ' kbyte';

        return $response->withHeader(self::HEADER, $memory);
    }

    protected function getPeakMemory(): float
    {
        return round(memory_get_peak_usage() / 1024, 2);
    }
}
