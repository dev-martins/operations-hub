<?php

namespace App\Support\Cache;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class QueueOverviewCache
{
    public function __construct(private readonly CacheFactory $cache) {}

    public function rememberForTenant(int $tenantId, Closure $resolver): array
    {
        return $this->store()->remember(
            $this->keyForTenant($tenantId),
            now()->addSeconds($this->ttl()),
            $resolver,
        );
    }

    public function forgetForTenant(int $tenantId): bool
    {
        return $this->store()->forget($this->keyForTenant($tenantId));
    }

    private function keyForTenant(int $tenantId): string
    {
        return sprintf('tenants:%d:queues:overview', $tenantId);
    }

    private function ttl(): int
    {
        return (int) config('operations.queue_overview_cache.ttl', 120);
    }

    private function store()
    {
        return $this->cache->store(config('cache.default'));
    }
}
