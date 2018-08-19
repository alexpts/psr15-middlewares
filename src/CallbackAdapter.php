<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallbackAdapter implements MiddlewareInterface
{
    /** @var callable */
    protected $realHandler;

    public function __construct(callable $handler)
    {
        $this->realHandler = $handler;
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return \call_user_func($this->realHandler, $request, $handler);
    }
}
