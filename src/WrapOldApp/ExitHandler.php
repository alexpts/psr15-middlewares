<?php
declare(strict_types=1);

namespace PTS\PSR15\Middlewares\WrapOldApp;

class ExitHandler
{
    /** @var callable|null */
    protected $handler;

    public function __construct()
    {
        register_shutdown_function($this);
    }

    public function register(callable $handler): void
    {
        $this->handler = $handler;
    }

    public function unregister(): void
    {
        $this->handler = null;
    }

    public function __invoke()
    {
        return null !== $this->handler ? call_user_func($this->handler) : null;
    }
}