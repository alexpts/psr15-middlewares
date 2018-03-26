<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\IsXhr;

class IsXhrTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['withAttribute'])
            ->getMockForAbstractClass();
        $request->expects(self::once())->method('withAttribute')->with('xhr', false)->willReturn($request);

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMockForAbstractClass();
        $next->expects(self::once())->method('handle')->with($request)
            ->willReturn($this->createMock(ResponseInterface::class));

        /** @var MockObject|IsXhr $middleware */
        $middleware = $this->getMockBuilder(IsXhr::class)
            ->setMethods(['hasXHR'])
            ->getMock();
        $middleware->expects(self::once())->method('hasXHR')->with($request)->willReturn(false);

        $middleware->process($request, $next);
    }

    /**
     * @param bool $expected
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderHasXHR
     */
    public function testHasXHR(string $header, bool $expected): void
    {
        $method = new ReflectionMethod(IsXhr::class, 'hasXHR');
        $method->setAccessible(true);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getHeader'])
            ->getMockForAbstractClass();
        $request->expects(self::once())->method('getHeader')->with('X-Requested-With')->willReturn($header);

        $actual = $method->invoke(new IsXhr, $request);
        self::assertSame($expected, $actual);
    }

    public function dataProviderHasXHR(): array
    {
        return [
            ['XMLHttpRequest', true],
            ['', false],
            ['ajax', false]
        ];
    }
}
