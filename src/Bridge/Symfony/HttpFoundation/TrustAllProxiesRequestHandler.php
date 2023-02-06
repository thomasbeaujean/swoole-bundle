<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class TrustAllProxiesRequestHandler implements RequestHandlerInterface, BootableInterface
{
    public const HEADER_X_FORWARDED_ALL = SymfonyRequest::HEADER_X_FORWARDED_FOR | SymfonyRequest::HEADER_X_FORWARDED_HOST | SymfonyRequest::HEADER_X_FORWARDED_PORT | SymfonyRequest::HEADER_X_FORWARDED_PROTO;

    private $decorated;
    private $trustAllProxies;
    private $trustedHeaderSet;

    public function __construct(RequestHandlerInterface $decorated, bool $trustAllProxies = false, int $trustedHeaderSet = null)
    {
        $this->decorated = $decorated;
        $this->trustAllProxies = $trustAllProxies;
        $this->trustedHeaderSet = $trustedHeaderSet;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        if (isset($runtimeConfiguration['trustAllProxies']) && true === $runtimeConfiguration['trustAllProxies']) {
            $this->trustAllProxies = true;
        }
        if (isset($runtimeConfiguration['trustedHeaderSet'])) {
            $this->trustedHeaderSet = $runtimeConfiguration['trustedHeaderSet'];
        }
    }

    public function trustAllProxies(): bool
    {
        return $this->trustAllProxies;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(SwooleRequest $request, SwooleResponse $response): void
    {
        $headers = self::HEADER_X_FORWARDED_ALL;

        if ($this->trustedHeaderSet) {
            $headers = $this->trustedHeaderSet;
        }

        if ($this->trustAllProxies()) {
            SymfonyRequest::setTrustedProxies(['127.0.0.1', $request->server['remote_addr']], $headers);
        }

        $this->decorated->handle($request, $response);
    }
}
