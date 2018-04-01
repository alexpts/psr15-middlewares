<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\ThrowableHandler;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;

class ThrowableHandlerTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    public function testCreate(): void
    {
        $handler = function (\Throwable $trow, ServerRequestInterface $request) {
            return new Response($trow->getMessage(), 500);
        };
        $middleware = new ThrowableHandler($handler);

        $property = new \ReflectionProperty(ThrowableHandler::class, 'handler');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);

        self::assertSame($handler, $actual);
    }

    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMockForAbstractClass();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMockForAbstractClass();
        $next->expects(self::once())->method('handle')->with($request)->willThrowException(new Exception('Some error'));

        $handler = function (\Throwable $trow, ServerRequestInterface $request) {
            return new HtmlResponse($trow->getMessage(), 500);
        };
        $middleware = new ThrowableHandler($handler);
        $response = $middleware->process($request, $next);

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Some error', $response->getBody()->getContents());
    }
}
