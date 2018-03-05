<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\ResponseTime;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class ResponseTimeTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testGetDiff(): void
    {
        $middleware = new ResponseTime;
        $method = new \ReflectionMethod(ResponseTime::class, 'getDiff');
        $method->setAccessible(true);
        $actual = $method->invoke($middleware, microtime(true));
        self::assertInternalType('float', $actual);
        self::assertLessThan(0.2, $actual);
    }

    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        $expectedTime = 1234.351;

        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['withHeader'])
            ->getMock();
        $response->expects(self::once())->method('withHeader')
            ->with('X-Response-Time', $expectedTime . 'ms')->willReturn($response);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->setMethods(['getServerParams'])
            ->getMock();
        $request->expects(self::once())->method('getServerParams')->willReturn([]);

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        $next->expects(self::once())->method('handle')->with($request)->willReturn($response);

        /** @var MockObject|ResponseTime $middleware */
        $middleware = $this->getMockBuilder(ResponseTime::class)
            ->setMethods(['getDiff'])
            ->getMock();
        $middleware->expects(self::once())->method('getDiff')->willReturn($expectedTime);

        $middleware->process($request, $next);
    }
}
