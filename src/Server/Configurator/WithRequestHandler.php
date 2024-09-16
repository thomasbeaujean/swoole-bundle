<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextStorage;
use OpenTelemetry\Contrib\Context\Swoole\SwooleContextStorage;
use Swoole\Http\Server;

final class WithRequestHandler implements ConfiguratorInterface
{
    private $requestHandler;

    public function __construct(RequestHandlerInterface $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        // for open telemetry, the swooleContextStorage must be used
        // if it is available, it is automatically enabled
        if (class_exists(SwooleContextStorage::class)) {
            // Use Swoole context storage
            Context::setStorage(new SwooleContextStorage(new ContextStorage()));
        }

        $server->on('request', [$this->requestHandler, 'handle']);
    }
}
