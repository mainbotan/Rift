<?php

namespace Rift\Core\Http\RateLimiter;

use PHPUnit\Framework\Constraint\Operator;
use Psr\Http\Message\ServerRequestInterface;
use Rift\Contracts\Cache\CacheInterface;
use Rift\Contracts\Middlewares\MiddlewareInterface;
use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;
use Rift\Core\Http\Request;

class RateLimitMiddleware implements MiddlewareInterface {
    public function __construct(
        private CacheInterface $cache
    ){ }

    public function execute(ServerRequestInterface $request): ResultType
    {
        $route = $request->getAttribute('route');

        if ($route['limit'] === null) {
            return Result::Success(null);
        }
        $limitData = $route['limit'];
        $interval = (int) $limitData['interval'];
        $maxAttempts = (int) $limitData['max_attempts'];
        $clientKey = $this->getClientKey($request, $limitData['strategy']);
        $clientKeyForRoute = "{$clientKey}:{$route['path']}";

        return $this->cache->has($clientKeyForRoute)
            ->catch(fn($errorObject) => $this->handleCacheError($errorObject))
            ->then(function($isSaved) use ($clientKeyForRoute, $maxAttempts, $interval) {
                if (!$isSaved) {
                    if ($maxAttempts > 0) {
                        return $this->cache->set($clientKeyForRoute, 1, $interval)
                            ->catch(fn($errorObject) => $this->handleCacheError($errorObject))
                            ->then(function() { 
                                return Result::Success(null); 
                            });
                    }
                    return Result::Failure(Result::HTTP_TOO_MANY_REQUESTS, "The request limit for the route has been exceeded. [{$maxAttempts}]");
                }
                return $this->cache->get($clientKeyForRoute)
                    ->catch(fn($errorObject) => $this->handleCacheError($errorObject))
                    ->then(function(int $requestsCount) use ($maxAttempts, $clientKeyForRoute) {
                        if ($requestsCount+1 > $maxAttempts) {
                            return Result::Failure(Result::HTTP_TOO_MANY_REQUESTS, "The request limit for the route has been exceeded. [{$maxAttempts}]");
                        }
                        
                        return $this->cache->increment($clientKeyForRoute) 
                             ->catch(fn($errorObject) => $this->handleCacheError($errorObject))
                             ->then(function() { 
                                    return Result::Success(null); 
                                });
                    });
            });
    }
    private function handleCacheError(ResultType $errorObject): ResultType {
        return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                "Cache module error: {$errorObject->error}",
                $errorObject->meta['debug']
            );
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