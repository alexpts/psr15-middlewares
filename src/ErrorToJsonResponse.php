<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PTS\Psr7\Response\JsonResponse;
use Throwable;
use function count;

class ErrorToJsonResponse implements MiddlewareInterface
{
    protected const HTTP_ERROR_STATUS_CODE = 500;

    public function __construct(
        protected int $statusCodeDefault = 500,
        protected bool $showError = false,
    ) {
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $targetLevel = count(ob_get_status(true));

        try {
            $response = $handler->handle($request);
        } catch (Throwable $throwable) {
            $response = $this->handleThrowable($throwable, $targetLevel);
        }

        return $response;
    }

    protected function handleThrowable(Throwable $throwable, int $targetLevel): ResponseInterface
    {
        $this->closeOutputBuffers($targetLevel);
        return $this->createResponse($throwable);
    }

    protected function createResponse(Throwable $throwable): ResponseInterface
    {
        $statusCode = $this->getStatusCode($throwable);
        $showError = $this->showError ?? $statusCode < self::HTTP_ERROR_STATUS_CODE;
        $message = $showError ? $throwable->getMessage() : 'Error';

        return new JsonResponse([
            'status' => 'error',
            'code' => $throwable->getCode(),
            'httpStatusCode' => $statusCode,
            'message' => $message
        ], $statusCode);
    }

    protected function getStatusCode(Throwable $throwable): int
    {
        if (method_exists($throwable, 'getStatusCode')) {
            return $throwable->getStatusCode();
        }

        return self::HTTP_ERROR_STATUS_CODE;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @param int $targetLevel
     * @param bool $flush
     *
     * @see original Symfony Response::closeOutputBuffers
     */
    protected function closeOutputBuffers(int $targetLevel = 0, bool $flush = false): void
    {
        $status = ob_get_status(true);
        $level = count($status);

        while ($level-- > $targetLevel) {
            $flush ? ob_end_flush() : ob_end_clean();
        }
    }
}
