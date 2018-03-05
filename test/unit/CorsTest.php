<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Plugins\Middlewares\Cors;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

class CorsTest extends TestCase
{
    public function testInvoke(): void
    {
        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        /** @var MiddlewareInterface|Cors|MockObject $middleware */
        $middleware = $this->getMockBuilder(Cors::class)
            ->disableOriginalConstructor()
            ->setMethods(['process'])
            ->getMock();
        $middleware->expects(self::once())->method('process')->with($request, $next)->willReturn($response);

        $actual = $middleware($request, $next);
        self::assertInstanceOf(ResponseInterface::class, $actual);
    }

    /**
     * @param array $hosts
     * @param string $header
     *
     * @dataProvider providerProcessOrigin
     */
    public function testProcessOrigin(array $hosts, string $header): void
    {
        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['withHeader'])
            ->getMock();
        $response->expects(self::once())->method('withHeader')
            ->with('Access-Control-Allow-Origin', $header)->willReturn($response);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        $next->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $middleware = new Cors($hosts);
        $middleware->process($request, $next);
    }

    public function providerProcessOrigin(): array
    {
        return [
            [
                ['https://a.com'], 'https://a.com'
            ],
            [
                ['https://a.ru', 'https://b.com'], 'https://a.ru https://b.com'
            ],
        ];
    }

    /**
     * @param array $headers
     * @param string $header
     *
     * @dataProvider providerAllowHeader
     */
    public function testProcessAllowHeaders(array $headers, string $header): void
    {
        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['withHeader'])
            ->getMock();
        $response->expects(self::once())->method('withHeader')
            ->with('Access-Control-Allow-Headers', $header)->willReturn($response);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        $next->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $middleware = new Cors([]);
        $middleware->setAllowHeaders($headers);
        $middleware->process($request, $next);
    }

    /**
     * @param array $headers
     * @param string $header
     *
     * @dataProvider providerAllowHeader
     */
    public function testProcessAllowMethods(array $headers, string $header): void
    {
        /** @var MockObject|ResponseInterface $middleware */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['withHeader'])
            ->getMock();
        $response->expects(self::once())->method('withHeader')
            ->with('Access-Control-Allow-Methods', $header)->willReturn($response);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        $next->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $middleware = new Cors([]);
        $middleware->setAllowMethods($headers);
        $middleware->process($request, $next);
    }

    public function providerAllowHeader(): array
    {
        return [
            [
                ['a'], 'a'
            ],
            [
                ['a', 'b'], 'a, b'
            ],
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function testSetAllowHeaders(): void
    {
        $headers = ['a', 'b'];

        $middleware = new Cors;
        $return = $middleware->setAllowHeaders($headers);
        self::assertInstanceOf(Cors::class, $return);

        $property = new \ReflectionProperty(Cors::class, 'allowHeaders');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);
        self::assertSame($headers, $actual);
    }

    /**
     * @param $headers array
     * @throws ReflectionException
     *
     * @dataProvider providerAddAllowHeader
     */
    public function testAddAllowHeaders(array $headers): void
    {
        $middleware = new Cors;
        foreach ($headers as $header) {
            $return = $middleware->addAllowHeader($header);
            self::assertInstanceOf(Cors::class, $return);
        }

        $property = new \ReflectionProperty(Cors::class, 'allowHeaders');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);
        self::assertSame($headers, $actual);
    }

    /**
     * @throws ReflectionException
     */
    public function testSetAllowMethods(): void
    {
        $headers = ['a', 'b'];

        $middleware = new Cors;
        $return = $middleware->setAllowMethods($headers);
        self::assertInstanceOf(Cors::class, $return);

        $property = new \ReflectionProperty(Cors::class, 'allowMethods');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);
        self::assertSame($headers, $actual);
    }

    public function providerAddAllowHeader(): array
    {
        return [
            [['a']],
            [['a', 'b']],
            [['a', 'b', 'c']]
        ];
    }
}
