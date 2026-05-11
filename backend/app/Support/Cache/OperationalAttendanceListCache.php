<?php

namespace App\Support\Cache;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class OperationalAttendanceListCache
{
    public function __construct(private readonly CacheFactory $cache) {}

    public function rememberForTenantPage(int $tenantId, int $page, Closure $resolver): array
    {
        return $this->store()->remember(
            $this->keyForTenantPage($tenantId, $page),
            now()->addSeconds($this->ttl()),
            $resolver,
        );
    }

    public function invalidateForTenant(int $tenantId): void
    {
        $this->store()->forever(
            $this->versionKeyForTenant($tenantId),
            $this->versionForTenant($tenantId) + 1,
        );
    }

    private function keyForTenantPage(int $tenantId, int $page): string
    {
        return sprintf(
            'tenants:%d:attendances:operational:v%d:page:%d',
            $tenantId,
            $this->versionForTenant($tenantId),
            $page,
        );
    }

    private function versionForTenant(int $tenantId): int
    {
        return max(1, (int) $this->store()->get($this->versionKeyForTenant($tenantId), 1));
    }

    private function versionKeyForTenant(int $tenantId): string
    {
        return sprintf('tenants:%d:attendances:operational:version', $tenantId);
    }

    private function ttl(): int
    {
        return (int) config('operations.operational_attendances_cache.ttl', 60);
    }

    private function store()
    {
        return $this->cache->store(config('cache.default'));
    }
}
