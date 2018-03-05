<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\Memory;
use Zend\Diactoros\Response;

class MemoryTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testGetPeakMemory(): void
    {
        $middleware = new Memory;
        $method = new \ReflectionMethod(Memory::class, 'getPeakMemory');
        $method->setAccessible(true);
        $actual = $method->invoke($middleware);
        self::assertInternalType('float', $actual);
    }

    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        $expectedMemory = 123;

        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['withHeader'])
            ->getMock();
        $response->expects(self::once())->method('withHeader')
            ->with('X-Memory', $expectedMemory . ' kbyte')->willReturn($response);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        $next->expects(self::once())->method('handle')->with($request)->willReturn($response);

        /** @var MockObject|Memory $middleware */
        $middleware = $this->getMockBuilder(Memory::class)
            ->setMethods(['getPeakMemory'])
            ->getMock();
        $middleware->expects(self::once())->method('getPeakMemory')->willReturn($expectedMemory);

        $middleware->process($request, $next);
    }
}
