<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\Etag;
use PTS\PSR15\Middlewares\StaticHeader;
use PTS\PSR15\Middlewares\StaticHeaderDefault;
use Zend\Diactoros\Response;

class StaticHeaderDefaultTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    public function testCreate(): void
    {
        $headers = ['Content-Type' => 'json', 'X-Powered-By' => 'PHP'];
        $middleware = new StaticHeaderDefault($headers);

        $property = new \ReflectionProperty(StaticHeader::class, 'headers');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);

        self::assertSame($headers, $actual);
    }

    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMockForAbstractClass();

        /** @var MockObject|ResponseInterface $request */
        $response = $this->getMockBuilder(ResponseInterface::class)->getMockForAbstractClass();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMockForAbstractClass();
        $next->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $headers = ['Content-Type' => 'json', 'X-Powered-By' => 'PHP'];
        /** @var MockObject|Etag $middleware */
        $middleware = $this->getMockBuilder(StaticHeader::class)
            ->setMethods(['withStaticHeaders'])
            ->setConstructorArgs([$headers])
            ->getMock();
        $middleware->expects(self::once())->method('withStaticHeaders')
            ->with($response, $headers)->willReturn($response);

        $middleware->process($request, $next);
    }

    /**
     * @param array $headers
     * @param array $extraHeaders
     * @param array $expected
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderWithStaticHeaders
     */
    public function testWithStaticHeaders(array $headers, array $extraHeaders, array $expected): void
    {
        $method = new \ReflectionMethod(StaticHeaderDefault::class, 'withStaticHeaders');
        $method->setAccessible(true);

        $md = new StaticHeaderDefault;
        $response = new Response;

        /** @var ResponseInterface $actual */
        $response = $method->invoke($md, $response, $headers);
        $actual = $method->invoke($md, $response, $extraHeaders);

        self::assertSame($expected, $actual->getHeaders());
    }

    public function dataProviderWithStaticHeaders(): array
    {
        return [
            [[], [], []],
            [
                [],
                ['Content-Type' => 'json'],
                ['Content-Type' => ['json']]
            ],
            [
                ['Content-Type' => 'json'],
                ['X-Memory' => '1024'],
                ['Content-Type' => ['json'], 'X-Memory' => ['1024']]
            ],
            [
                ['Content-Type' => ['json', 'vue']],
                ['Content-Type' => ['json', 'vue']],
                ['Content-Type' => ['json', 'vue']],
            ],
            [
                ['Content-Type' => 'json'],
                ['Content-Type' => 'json2'],
                ['Content-Type' => ['json']],
            ],
            [
                ['Content-Type' => ['json']],
                ['Content-Type' => 'json2'],
                ['Content-Type' => ['json']],
            ],
        ];
    }
}
