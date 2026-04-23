<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_users_for_the_authenticated_tenant(): void
    {
        $user = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

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

    public function test_it_blocks_user_listing_for_roles_without_permission(): void
    {
        $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'operator',
            ]),
        );

        $response = $this->getJson('/api/v1/users');

        $response->assertForbidden()
            ->assertJsonPath('required_permission', 'users.view');
    }

    public function test_admin_can_update_role_for_user_from_same_tenant(): void
    {
        $admin = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'admin',
            ]),
        );

        $operator = $this->createUserForTenant($admin->tenant, [
            'name' => 'Operador de Teste',
            'email' => 'operador.teste@example.com',
            'role' => 'operator',
        ]);

        $response = $this->patchJson("/api/v1/users/{$operator->id}/role", [
            'role' => 'viewer',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'viewer')
            ->assertJsonPath('data.role_context.key', 'viewer');
    }

    public function test_supervisor_cannot_update_roles_without_acl_manage_permission(): void
    {
        $supervisor = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'supervisor',
            ]),
        );

        $operator = $this->createUserForTenant($supervisor->tenant, [
            'role' => 'operator',
        ]);

        $response = $this->patchJson("/api/v1/users/{$operator->id}/role", [
            'role' => 'viewer',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('required_permission', 'acl.manage');
    }

    public function test_admin_cannot_change_own_role_in_this_stage(): void
    {
        $admin = $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'admin',
            ]),
        );

        $response = $this->patchJson("/api/v1/users/{$admin->id}/role", [
            'role' => 'viewer',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }
}
