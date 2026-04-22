<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_users_for_the_authenticated_tenant(): void
    {
        $user = $this->actingAsTenantUser();

        $this->createUserForTenant($user->tenant, [
            'name' => 'Bruna Operacoes',
            'email' => 'bruna@example.com',
        ]);

        $this->createUserForTenant($user->tenant, [
            'name' => 'Carlos Integracoes',
            'email' => 'carlos@example.com',
        ]);

        $this->createUserForTenant(
            $this->createTenant([
                'name' => 'Tenant Externo',
                'slug' => 'tenant-externo',
            ]),
            [
                'name' => 'Usuario Externo',
                'email' => 'externo@example.com',
            ],
        );

        $response = $this->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['email' => 'externo@example.com']);
    }
}
