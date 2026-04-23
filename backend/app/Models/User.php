<?php

namespace App\Models;

use App\Support\Acl\AclCatalogue;
use App\Support\Acl\Permission;
use App\Support\Acl\Role;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['tenant_id', 'name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function aclRole(): Role
    {
        return Role::tryFrom((string) $this->role) ?? Role::Operator;
    }

    public function hasPermission(Permission $permission): bool
    {
        return AclCatalogue::hasPermission($this->aclRole(), $permission);
    }

    /**
     * @return array<string, string>
     */
    public function roleContext(): array
    {
        $role = $this->aclRole();

        return [
            'key' => $role->value,
            'label' => $role->label(),
            'description' => $role->description(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function permissionContexts(): array
    {
        return array_map(
            fn (Permission $permission): array => AclCatalogue::permissionDefinition($permission),
            AclCatalogue::permissionsForRole($this->aclRole()),
        );
    }
}
