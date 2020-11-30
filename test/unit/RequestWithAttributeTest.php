<?php
declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\Etag;
use PTS\PSR15\Middlewares\RequestWithAttribute;
use PTS\Psr7\ServerRequest;
use PTS\Psr7\Uri;

class RequestWithAttributeTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    public function testCreate(): void
    {
        $attributes = ['Content-Type' => 'json', 'X-Powered-By' => 'PHP'];
        $middleware = new RequestWithAttribute($attributes);

        $property = new ReflectionProperty(RequestWithAttribute::class, 'attributes');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);

        self::assertSame($attributes, $actual);
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

        $attributes = ['Content-Type' => 'json', 'X-Powered-By' => 'PHP'];
        /** @var MockObject|Etag $middleware */
        $middleware = $this->getMockBuilder(RequestWithAttribute::class)
            ->setMethods(['withAttributes'])
            ->setConstructorArgs([$attributes])
            ->getMock();
        $middleware->expects(self::once())->method('withAttributes')
            ->with($request, $attributes)->willReturn($request);

        $middleware->process($request, $next);
    }

    /**
     * @param array $attributes
     * @param array $expected
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderWithAttributes
     */
    public function testWithAttributes(array $attributes, array $expected): void
    {
        $method = new ReflectionMethod(RequestWithAttribute::class, 'withAttributes');
        $method->setAccessible(true);

        $request = new ServerRequest('GET', new Uri('/'));
        $actual = $method->invoke(new RequestWithAttribute, $request, $attributes);

        self::assertSame($expected, $actual->getAttributes());
    }

    public function dataProviderWithAttributes(): array
    {
        return [
            [[], []],
            'string value' => [
                ['Content-Type' => 'json'],
                ['Content-Type' => 'json']
            ],
            'array value' => [
                ['Content-Type' => ['json']],
                ['Content-Type' => ['json']]
            ],
            'array values' => [
                ['Content-Type' => ['json', 'vue']],
                ['Content-Type' => ['json', 'vue']]
            ],
            'some values' => [
                ['Content-Type' => 'json', 'x-p' => '2'],
                ['Content-Type' => 'json', 'x-p' => '2'],
            ],

        ];
    }
}
