<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class AclUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate([
            'slug' => 'montreal-operacoes',
        ], [
            'name' => 'Montreal Operacoes',
            'active' => true,
        ]);

        $users = [
            [
                'name' => 'Alice Admin',
                'email' => 'alice.admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Sofia Supervisor',
                'email' => 'sofia.supervisor@example.com',
                'role' => 'supervisor',
            ],
            [
                'name' => 'Otavio Operator',
                'email' => 'otavio.operator@example.com',
                'role' => 'operator',
            ],
            [
                'name' => 'Vera Viewer',
                'email' => 'vera.viewer@example.com',
                'role' => 'viewer',
            ],
        ];

        foreach ($users as $attributes) {
            $user = User::query()->firstOrCreate([
                'email' => $attributes['email'],
            ], [
                'tenant_id' => $tenant->id,
                'name' => $attributes['name'],
                'password' => bcrypt('password'),
                'role' => $attributes['role'],
            ]);

            $user->forceFill([
                'tenant_id' => $tenant->id,
                'name' => $attributes['name'],
                'role' => $attributes['role'],
            ])->save();
        }
    }
}
