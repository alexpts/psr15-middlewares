<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\Etag;
use PTS\PSR15\Middlewares\StaticHeader;
use Zend\Diactoros\Response;

class StaticHeaderTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    public function testCreate(): void
    {
        $headers = ['Content-Type' => 'json', 'X-Powered-By' => 'PHP'];
        $middleware = new StaticHeader($headers);

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
     * @param array $expected
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderWithStaticHeaders
     */
    public function testWithStaticHeaders(array $headers, array $expected): void
    {
        $method = new \ReflectionMethod(StaticHeader::class, 'withStaticHeaders');
        $method->setAccessible(true);

        $response = new Response;
        /** @var ResponseInterface $actual */
        $actual = $method->invoke(new StaticHeader, $response, $headers);

        self::assertSame($expected, $actual->getHeaders());
    }

    public function dataProviderWithStaticHeaders(): array
    {
        return [
            [[], []],
            'string value' => [
                ['Content-Type' => 'json'],
                ['Content-Type' => ['json']]
            ],
            'array value' => [
                ['Content-Type' => ['json']],
                ['Content-Type' => ['json']]
            ],
            'array values' => [
                ['Content-Type' => ['json', 'vue']],
                ['Content-Type' => ['json', 'vue']]
            ],
        ];
    }
}
