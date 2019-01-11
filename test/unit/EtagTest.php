<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\Etag;
use Zend\Diactoros\Stream;

class EtagTest extends TestCase
{
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

        /** @var MockObject|Etag $middleware */
        $middleware = $this->getMockBuilder(Etag::class)
            ->setMethods(['addEtag'])
            ->getMock();
        $middleware->expects(self::once())->method('addEtag')->with($request, $response)->willReturn($response);

        $middleware->process($request, $next);
    }

    /**
     * @param bool $can
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderAddEtag
     */
    public function testAddEtag(bool $can): void
    {
        $method = new \ReflectionMethod(Etag::class, 'addEtag');
        $method->setAccessible(true);

        $stream = $this->getMockBuilder(StreamInterface::class)
            ->setMethods(['getContents'])
            ->getMockForAbstractClass();
        $stream->expects(self::exactly($can))->method('getContents')->willReturn(md5(time()));

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getBody'])
            ->getMockForAbstractClass();

        /** @var MockObject|ResponseInterface $request */
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['withHeader', 'getBody'])
            ->getMockForAbstractClass();
        $response->expects(self::exactly($can))->method('withHeader')->with('Etag')->willReturnSelf();
        $response->expects(self::exactly($can))->method('getBody')->willReturn($stream);

        $mock = $this->getMockBuilder(Etag::class)
            ->setMethods(['canEtag', 'setNotModifyHeader'])
            ->getMock();
        $mock->expects(self::once())->method('canEtag')->with($request, $response)->willReturn($can);
        $mock->expects(self::exactly($can))->method('setNotModifyHeader')->with($request, $response);

        $method->invoke($mock, $request, $response);
    }

    public function dataProviderAddEtag(): array
    {
        return [
            [true, 'asdasd'],
            [false]
        ];
    }


    /**
     * @param string $etag
     * @param string $newEtag
     * @param bool $isCache
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderSetNotModifyHeader
     */
    public function testSetNotModifyHeader(string $etag, string $newEtag, bool $isCache): void
    {
        $method = new \ReflectionMethod(Etag::class, 'setNotModifyHeader');
        $method->setAccessible(true);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getHeaderLine'])
            ->getMockForAbstractClass();
        $request->expects(self::once())->method('getHeaderLine')->willReturn($etag);

        /** @var MockObject|ResponseInterface $request */
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['withStatus', 'getBody', 'withBody'])
            ->getMockForAbstractClass();
        $response->expects(self::exactly($isCache))->method('withStatus')->with(304)->willReturnSelf();
        $response->method('withBody')->willReturnSelf();
        $response->method('getBody')->willReturn(new Stream('php://memory', 'wb+'));

        $method->invoke(new Etag, $request, $response, $newEtag);
    }

    public function dataProviderSetNotModifyHeader(): array
    {
        return [
            ['sd', 'sd', true],
            ['sd', 'sd2', false],
            ['W/213', '213', false],
            ['W/213', 'W/212', false],
        ];
    }

    /**
     * @param string $httpMethod
     * @param int $status
     * @param bool $hasHeader
     * @param bool $expected
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderCanEtag
     */
    public function testCanEtag(string $httpMethod, int $status, bool $expected): void
    {
        $method = new \ReflectionMethod(Etag::class, 'canEtag');
        $method->setAccessible(true);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'hasHeader'])
            ->getMockForAbstractClass();
        $request->expects(self::once())->method('getMethod')->willReturn($httpMethod);

        /** @var MockObject|ResponseInterface $request */
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['getStatusCode'])
            ->getMockForAbstractClass();
        $response->method('getStatusCode')->willReturn($status);

        $actual = $method->invoke(new Etag, $request, $response);
        self::assertSame($expected, $actual);
    }

    public function dataProviderCanEtag(): array
    {
        return [
            ['GET', 200, true],
            ['GET', 201, false],
            ['GET', 200, true],
            ['POST', 200, false],
            ['DELETE', 201, false],
            ['GET', 500, false],
            ['GET', 500, false],
        ];
    }
}
