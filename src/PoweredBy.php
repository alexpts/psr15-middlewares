<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PoweredBy implements MiddlewareInterface
{
    protected const HEADER = 'X-Powered-By';

    /** @var string */
    protected $name;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next) : ResponseInterface
    {
        $response = $next->handle($request);

        return $response->withHeader(self::HEADER, $this->name);
    }
}
