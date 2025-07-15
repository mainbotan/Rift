<?php

namespace Rift\Core\Http\RateLimiter;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Contracts\Cache\CacheInterface;
use Rift\Contracts\Middlewares\MiddlewareInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Http\Request;

class RateLimitMiddleware implements MiddlewareInterface {
    public function __construct(
        private CacheInterface $cache
    ){ }

    public function execute(ServerRequestInterface $request): OperationOutcome
    {
        $route = $request->getAttribute('route');

        if ($route['limit'] === null) {
            return Operation::success(null);
        }
        $limitData = $route['limit'];
        $clientKey = $this->getClientKey($request, $limitData['strategy']);

        var_dump($clientKey);
        var_dump($this->cache->get($clientKey));
        return Operation::success(null);
    }

    private function getClientKey(
        ServerRequestInterface $request, 
        string $strategy
    ): string {
        $ip = $request->getServerParams()['REMOTE_ADDR'];
        
        return match ($strategy) {
            'ip' => $ip,
            'ip+agent' => $ip . $request->getHeaderLine('User-Agent'),
            'ip+lang' => $ip . $request->getHeaderLine('Accept-Language'),
            default => throw new \RuntimeException("Unknown rate-limit strategy: {$strategy}")
        };
    }
}