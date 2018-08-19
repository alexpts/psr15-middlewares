<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\ResponseEmit;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

class ResponseEmitTest extends TestCase
{
    public function testInvoke(): void
    {
        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        /** @var MiddlewareInterface|ResponseEmit|MockObject $middleware */
        $middleware = $this->getMockBuilder(ResponseEmit::class)
            ->disableOriginalConstructor()
            ->setMethods(['process'])
            ->getMock();
        $middleware->expects(self::once())->method('process')->with($request, $next)->willReturn($response);

        $actual = $middleware($request, $next);
        self::assertInstanceOf(ResponseInterface::class, $actual);
    }

    /**
     * @throws Throwable
     */
    public function testGetResponseEmitter(): void
    {
        $emitter = new SapiEmitter;
        $middleware = new ResponseEmit($emitter);

        $method = new \ReflectionMethod(ResponseEmit::class, 'getResponseEmitter');
        $method->setAccessible(true);
        $actual = $method->invoke($middleware);

        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
        $this->assertInstanceOf(EmitterInterface::class, $actual);
    }

    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        $next->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $emitter = $this->getMockBuilder(EmitterInterface::class)
            ->setMethods(['emit'])
            ->getMock();
        $emitter->expects(self::once())->method('emit')->with($response);

        /** @var MockObject|ResponseEmit $middleware */
        $middleware = $this->getMockBuilder(ResponseEmit::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResponseEmitter'])
            ->getMock();
        $middleware->expects(self::once())->method('getResponseEmitter')->willReturn($emitter);

        $actual = $middleware->process($request, $next);
        self::assertSame($response, $actual);
    }
}
