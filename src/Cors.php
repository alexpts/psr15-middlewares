<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @docs https://www.w3.org/TR/cors/
 */
class Cors implements MiddlewareInterface
{
	protected const ORIGIN = 'Access-Control-Allow-Origin';
	protected const HEADERS = 'Access-Control-Allow-Headers';
	protected const METHODS = 'Access-Control-Allow-Methods';

	/** @var string[] */
	protected $originHosts = [];
	/** @var string[] */
	protected $allowHeaders = [];
    /** @var string[] */
	protected $allowMethods = [];

	public function __construct(array $originHosts = ['*'])
	{
		$this->originHosts = $originHosts;
	}

	public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        return $this->process($request, $next);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $response = $next->handle($request);

        if (\count($this->originHosts)) {
            $response = $response->withHeader(self::ORIGIN, implode(' ', $this->originHosts));
        }

        if (\count($this->allowHeaders)) {
            $response = $response->withHeader(self::HEADERS, implode(', ', $this->allowHeaders));
        }

        if (\count($this->allowMethods)) {
            $response = $response->withHeader(self::METHODS, implode(', ', $this->allowMethods));
        }

        return $response;
    }

    /**
     * @param string[] $headers
     *
     * @return $this
     */
	public function setAllowHeaders(array $headers = []): self
    {
        $this->allowHeaders = $headers;
        return $this;
    }

    public function addAllowHeader(string $header): self
    {
        $this->allowHeaders[] = $header;
        return $this;
    }

    /**
     * @param string[] $headers
     *
     * @return $this
     */
    public function setAllowMethods(array $headers = []): self
    {
        $this->allowMethods = $headers;
        return $this;
    }
}
