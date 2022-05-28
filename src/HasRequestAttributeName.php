<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\Tools\DuplicateKeyException;

class HasRequestAttributeName implements MiddlewareInterface
{
    protected array $attributes = [];
    protected Exception $exception;

    public function __construct(array $attributes, Exception $exception = null)
    {
        $this->attributes = $attributes;
        $this->exception = $exception ?? new InvalidArgumentException('Check attribute error');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->checkAttributes($request, $this->attributes);

        return $handler->handle($request);
    }

    protected function checkAttributes(ServerRequestInterface $request, array $attributes): ServerRequestInterface
    {
        foreach ($attributes as $name) {
            if (!$request->getAttribute($name)) {
                throw $this->exception;
            }
        }

        return $request;
    }
}
