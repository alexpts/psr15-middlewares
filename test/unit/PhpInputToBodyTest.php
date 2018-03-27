<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\PhpInputToBody;

class PhpInputToBodyTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        $body = ['a' => 1, 'b' => 2];
        $bodyFromInput = ['a' => 3, 'c' => 4];

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getParsedBody', 'withParsedBody'])
            ->getMockForAbstractClass();
        $request->expects(self::once())->method('getParsedBody')->willReturn($body);
        $request->expects(self::once())->method('withParsedBody')
            ->with(array_merge($bodyFromInput, $body))->willReturnSelf();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMockForAbstractClass();
        $next->expects(self::once())->method('handle')->with($request)
            ->willReturn($this->createMock(ResponseInterface::class));

        /** @var MockObject|PhpInputToBody $middleware */
        $middleware = $this->getMockBuilder(PhpInputToBody::class)
            ->setMethods(['parseBody'])
            ->getMock();
        $middleware->expects(self::once())->method('parseBody')->with($request)->willReturn($bodyFromInput);

        $middleware->process($request, $next);
    }

    /**
     * @param string $streamData
     * @param array $expected
     *
     * @throws ReflectionException
     *
     * @dataProvider dataProviderHasXHR
     */
    public function testParseBody(string $streamData, array $expected): void
    {
        $method = new ReflectionMethod(PhpInputToBody::class, 'parseBody');
        $method->setAccessible(true);

        $stream = $this->getMockBuilder(StreamInterface::class)
            ->setMethods(['getContents'])
            ->getMockForAbstractClass();
        $stream->expects(self::once())->method('getContents')->willReturn($streamData);

        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getBody'])
            ->getMockForAbstractClass();
        $request->expects(self::once())->method('getBody')->willReturn($stream);

        $actual = $method->invoke(new PhpInputToBody, $request);
        self::assertSame($expected, $actual);
    }

    public function dataProviderHasXHR(): array
    {
        return [
            ['', []],
            ['first=value&arr[]=foo+bar&arr[]=baz', [
                'first' => 'value',
                'arr' => ['foo bar', 'baz']
            ]],
        ];
    }
}
