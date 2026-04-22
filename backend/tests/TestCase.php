<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Tenant de Teste',
            'slug' => 'tenant-teste',
            'active' => true,
        ], $attributes));
    }

    protected function createUserForTenant(?Tenant $tenant = null, array $attributes = []): User
    {
        $tenant ??= $this->createTenant();

        return User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
        ], $attributes));
    }

    protected function actingAsTenantUser(?User $user = null): User
    {
        $user ??= $this->createUserForTenant();

        Passport::actingAs($user);

        return $user;
    }

    protected function createPassportPersonalClient(): void
    {
        $provider = config('auth.guards.api.provider');

        $alreadyExists = Passport::client()
            ->newQuery()
            ->where('revoked', false)
            ->where(function ($query) use ($provider): void {
                $query->whereNull('provider')
                    ->orWhere('provider', $provider);
            })
            ->get()
            ->contains(fn ($client) => $client->hasGrantType('personal_access'));

        if ($alreadyExists) {
            return;
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Test Personal Access Client',
            $provider,
        );
    }
}
