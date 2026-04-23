<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AclApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_acl_catalogue_for_authorized_roles(): void
    {
        $supervisor = $this->createUserForTenant(attributes: [
            'role' => 'supervisor',
        ]);
        $this->createUserForTenant($supervisor->tenant, [
            'role' => 'admin',
            'email' => 'admin.governanca@example.com',
        ]);

        $this->actingAsTenantUser($supervisor);

        $response = $this->getJson('/api/v1/auth/acl');

        $response->assertOk()
            ->assertJsonPath('data.current_user.role.key', 'supervisor')
            ->assertJsonPath('data.roles.0.key', 'admin')
            ->assertJsonPath('data.role_summary.0.key', 'admin')
            ->assertJsonFragment([
                'key' => 'attendances.assign',
                'label' => 'Atribuir atendimentos',
            ]);
    }

    public function test_it_blocks_acl_catalogue_for_roles_without_acl_visibility(): void
    {
        $this->actingAsTenantUser(
            $this->createUserForTenant(attributes: [
                'role' => 'operator',
            ]),
        );

        $response = $this->getJson('/api/v1/auth/acl');

        $response->assertForbidden()
            ->assertJsonPath('required_permission', 'acl.view');
    }
}
