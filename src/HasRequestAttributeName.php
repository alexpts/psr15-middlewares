<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HasRequestAttributeName implements MiddlewareInterface
{
    /** @var array */
    protected $attributes = [];
    /** @var Exception */
    protected $exception;

    public function __construct(array $attributes, Exception $exception)
    {
        $this->attributes = $attributes;
        $this->exception = $exception;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->checkAttributes($request, $this->attributes);

        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $attributes
     *
     * @return ServerRequestInterface
     * @throws Exception
     */
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
