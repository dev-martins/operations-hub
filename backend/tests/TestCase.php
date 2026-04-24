<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    protected static bool $passportKeysBootstrapped = false;

    private const TEST_APP_KEY = 'base64:H3Qvt6/AhsPNH6YK2Z6PVzIOr3GN2x1Y34v9m7vWEto=';

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureApplicationKey();
        $this->bootstrapPassportKeys();
    }

    protected function ensureApplicationKey(): void
    {
        if (filled(config('app.key'))) {
            return;
        }

        config()->set('app.key', self::TEST_APP_KEY);
    }

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

    protected function bootstrapPassportKeys(): void
    {
        if (self::$passportKeysBootstrapped) {
            Passport::loadKeysFrom(storage_path());

            return;
        }

        $this->artisan('passport:keys', ['--force' => true])->assertExitCode(0);

        Passport::loadKeysFrom(storage_path());

        self::$passportKeysBootstrapped = true;
    }
}
