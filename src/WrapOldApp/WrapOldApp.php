<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares\WrapOldApp;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

class WrapOldApp implements MiddlewareInterface
{
    /** @var callable */
    protected $runnerApp;
    /** @var ExitHandler */
    protected $exitHandler;
    /** @var int */
    protected $currentLevelBuffer = 0;

    public function __construct(callable $runnerApp, ExitHandler $exitHandler)
    {
        $this->runnerApp = $runnerApp;
        $this->exitHandler = $exitHandler;
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->startBuffer();
        $this->exitHandler->register([$this, 'onExit']);

        // @todo формат ответа ожидания определить как-то нужно
        $this->syncGlobalEnv($request);

        try {
            \call_user_func($this->runnerApp, $request);
        } catch (\Throwable $trow) {
            $this->stopBuffer();
            return new HtmlResponse($trow->getMessage(), 500);
        } finally {
            $this->exitHandler->unregister();
        }

        return $this->afterHandle();
    }

    /**
     * @todo подумать про восстановление env после обработки
     * @param ServerRequestInterface $request
     */
    protected function syncGlobalEnv(ServerRequestInterface $request): void
    {
        $_SERVER = $request->getServerParams();
        $_COOKIE = $request->getCookieParams();
        $_GET = $request->getQueryParams();

        $_POST = (array)$request->getParsedBody();
        $_FILES = $request->getUploadedFiles();
        //$request->body = Stream::open('php://input', 'r'); // @todo replace input stream
    }

    protected function afterHandle(): ResponseInterface
    {
        $out = $this->getBuffer();
        $this->stopBuffer();
        $this->exitHandler->unregister();

        $headers = $this->getHeaders();

        return new HtmlResponse($out ?? '', 200, $headers);
    }

    public function onExit(): ResponseInterface
    {
        return $this->afterHandle();
    }

    protected function startBuffer(): void
    {
        ob_start();
        $this->currentLevelBuffer = \count(ob_get_status(true));
    }

    protected function getBuffer(): string
    {
        $out = ob_get_contents();
        return $out === false ? '' : $out;
    }

    protected function stopBuffer(): void
    {
        $status = ob_get_status(true);
        if (\count($status) === $this->currentLevelBuffer) {
            ob_end_clean();
        }
    }

    protected function isHeaderSent(): bool
    {
        return headers_sent();
    }

    protected function getHeaders(): array
    {
        $headers = headers_list();

        $formatted = [];
        foreach ($headers as $header) {
            [$name, $value] = explode(': ', $header, 2);
            $formatted[$name] = $value;
        }

        return $formatted;
    }
}
