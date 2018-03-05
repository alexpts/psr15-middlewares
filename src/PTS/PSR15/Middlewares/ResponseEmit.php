<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmitterInterface;

class ResponseEmit implements MiddlewareInterface
{
    /** @var EmitterInterface */
    protected $emitter;

    public function __construct(EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next) : ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = $next->handle($request);
		$this->getResponseEmitter()->emit($response);
        return $response;
    }

    protected function getResponseEmitter(): EmitterInterface
	{
		return $this->emitter;
	}
}
