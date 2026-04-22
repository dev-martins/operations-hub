<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_authenticates_with_passport_and_returns_user_with_tenant(): void
    {
        $this->createPassportPersonalClient();

        $tenant = $this->createTenant([
            'name' => 'Tenant Operacional',
            'slug' => 'tenant-operacional',
        ]);

        $user = $this->createUserForTenant($tenant, [
            'name' => 'Henri Operacoes',
            'email' => 'henri@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'henri@example.com')
            ->assertJsonPath('data.user.tenant.name', 'Tenant Operacional');
    }

    public function test_it_returns_authenticated_user_context(): void
    {
        $user = $this->actingAsTenantUser(
            $this->createUserForTenant(
                $this->createTenant([
                    'name' => 'Tenant Contexto',
                    'slug' => 'tenant-contexto',
                ]),
                [
                    'name' => 'Bruna Contexto',
                    'email' => 'bruna@example.com',
                    'role' => 'operator',
                ],
            )
        );

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.name', 'Bruna Contexto')
            ->assertJsonPath('data.tenant_id', $user->tenant_id)
            ->assertJsonPath('data.tenant.name', 'Tenant Contexto');
    }

    public function test_it_logs_out_the_authenticated_user(): void
    {
        $user = $this->actingAsTenantUser();

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Sessão encerrada com sucesso.');
    }
}
