<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use PTS\Tools\TraceFormatter;
use Throwable;
use function call_user_func;

/**
 * Логирование необработанных всплывающих ошибок
 */
class LogError implements MiddlewareInterface
{
    protected LoggerInterface $logger;
    protected string $defaultLevel = LogLevel::ERROR;
    protected TraceFormatter $traceFormatter;

    public function __construct(
        LoggerInterface $logger,
        TraceFormatter $traceFormatter,
        $defaultLevel = LogLevel::ERROR
    ) {
        $this->logger = $logger;
        $this->defaultLevel = $defaultLevel;
        $this->traceFormatter = $traceFormatter;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (Throwable $throwable) {
            $this->log($throwable);
            throw $throwable;
        }

        return $response;
    }

    /**
     * @param Throwable $throwable
     */
    protected function log(Throwable $throwable): void
    {
        $log = $this->createLogMessage($throwable);
        $message = $log['message'];
        unset($log['message']);

        $level = $context['error_level'] ?? $this->defaultLevel;
        call_user_func([$this->logger, 'log'], $level, $message, [
            'exception' => $log
        ]);
    }

    /**
     * Преобразует рекурсивно всю цепочку исключений в запись лога
     *
     * @param Throwable $throwable
     *
     * @return array
     */
    protected function createLogMessage(Throwable $throwable): array
    {
        $log = [
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'trace' => $this->traceFormatter->formatTrace($throwable->getTrace()),
        ];

        if ($throwable->getPrevious()) {
            $log['prev'] = $this->createLogMessage($throwable->getPrevious());
        }

        return $log;
    }
}
