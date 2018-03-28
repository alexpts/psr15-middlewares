<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;

class StaticHeaderDefault extends StaticHeader
{
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
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $header);
            }
        }

        return $response;
    }
}
