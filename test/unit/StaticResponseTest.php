<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\StaticResponse;
use Zend\Diactoros\Response\JsonResponse;

class StaticResponseTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    public function testCreate(): void
    {
        $response = new JsonResponse([]);
        $middleware = new StaticResponse($response);

        $property = new \ReflectionProperty(StaticResponse::class, 'response');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);

        self::assertInstanceOf(JsonResponse::class, $actual);
    }

    public function testProcess(): void
    {
        $response = new JsonResponse([]);
        $middleware = new StaticResponse($response);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMockForAbstractClass();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)->getMockForAbstractClass();

        $response = $middleware->process($request, $next);

        self::assertInstanceOf(JsonResponse::class, $response);
    }
}
