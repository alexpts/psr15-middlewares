<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function call_user_func;

class LogError implements MiddlewareInterface
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var int|string */
    protected $defaultLevel;

    /**
     * LogException constructor.
     *
     * @param LoggerInterface $logger
     * @param int|string $defaultLevel
     */
    public function __construct(LoggerInterface $logger, $defaultLevel = 400)
    {
        $this->logger = $logger;
        $this->defaultLevel = $defaultLevel;
    }

    /**
     * @inheritdoc
     * @throws \Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (\Throwable $throwable) {
            $this->log($throwable, $request);
            throw $throwable;
        }

        return $response;
    }

    protected function log(Throwable $throwable, ServerRequestInterface $request): void
    {
        ['message' => $message, 'context' => $context] = $this->createLogMessage($throwable, $request);
        $level = $context['error_level'] ?? $this->defaultLevel;

        call_user_func([$this->logger, 'log'], $level, $message, $context);
    }

    protected function createLogMessage(Throwable $throwable, ServerRequestInterface $request): array
    {
        $log = [
            'message' => $throwable->getMessage(),
            'context' => [
                'code' => $throwable->getCode(),
                'trace' => $throwable->getTrace(),
                'requestId' => $request->getAttribute('requestId')
            ]
        ];

        if ($throwable->getPrevious()) {
            $log['context']['prev'] = $this->createLogMessage($throwable->getPrevious(), $request);
            unset($log['context']['prev']['context']['requestId']);
        }

        return $log;
    }
}
