<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StaticHeader implements MiddlewareInterface
{
	protected $headers = [];

	public function __construct(array $headers = [])
	{
		$this->headers = $headers;
	}

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $this->withStaticHeaders($response, $this->headers);
    }

    /**
     * @param ResponseInterface $response
     * @param array $headers
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function withStaticHeaders(ResponseInterface $response, array $headers): ResponseInterface
    {
        foreach ($headers as $name => $header) {
            $header = \is_array($header) ? \implode(', ', $header) : $header;
            $response = $response->withHeader($name, $header);
        }

        return $response;
    }
}
