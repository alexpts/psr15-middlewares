<?php
declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\PSR15\Middlewares\ThrowableHandler;
use PTS\Psr7\Response;

class ThrowableHandlerTest extends TestCase
{

    public function testCreate(): void
    {
        $handler = static function (Throwable $trow) {
            return new Response(500, [], $trow->getMessage());
        };
        $middleware = new ThrowableHandler($handler);

        $property = new ReflectionProperty(ThrowableHandler::class, 'handler');
        $property->setAccessible(true);
        $actual = $property->getValue($middleware);

        self::assertSame($handler, $actual);
    }

    /**
     * @throws Throwable
     */
    public function testProcess(): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMockForAbstractClass();

        /** @var MockObject|RequestHandlerInterface $next */
        $next = $this->getMockBuilder(RequestHandlerInterface::class)
            ->onlyMethods(['handle'])
            ->getMockForAbstractClass();
        $next->expects(self::once())->method('handle')->with($request)->willThrowException(new Exception('Some error'));

        $handler = static function (Throwable $trow) {
            return new Response(500, [], $trow->getMessage());
        };
        $middleware = new ThrowableHandler($handler);
        $response = $middleware->process($request, $next);

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Some error', (string)$response->getBody());
    }
}
