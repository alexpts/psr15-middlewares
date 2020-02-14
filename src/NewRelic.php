<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function extension_loaded;

class NewRelic implements MiddlewareInterface
{

    /** @var bool */
    protected $enabled;
    /** @var null|string */
    protected $appName;
    /** @var null|string */
    protected $licenseKey;
    /** @var bool */
    protected $isWeb = true;

    /**
     * @param string|null $appName
     * @param string|null $licenseKey
     * @param bool $isWeb
     */
    public function __construct(string $appName = null, string $licenseKey = null, bool $isWeb = true)
    {
        $this->enabled = $this->isEnableExtension();
        $this->appName = $appName ?? ini_get('newrelic.appname');
        $this->licenseKey = $licenseKey;
        $this->isWeb = $isWeb;
    }

    protected function isEnableExtension(): bool
    {
        return extension_loaded('newrelic');
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $this->startTransaction();
        $response = $handler->handle($request);
        $this->endTransaction();

        return $response;
    }

    protected function startTransaction(): void
    {
        if ($this->enabled) {
            newrelic_start_transaction($this->appName, $this->licenseKey ?? '');
            newrelic_background_job(!$this->isWeb);
        }
    }

    protected function endTransaction(): void
    {
        if ($this->enabled) {
            newrelic_end_transaction();
        }
    }
}
