<?php

namespace App\Support\Acl;

final class AclCatalogue
{
    /**
     * @return array<int, Permission>
     */
    public static function permissionsForRole(Role $role): array
    {
        return match ($role) {
            Role::Admin => [
                Permission::AclView,
                Permission::AclManage,
                Permission::UsersView,
                Permission::QueuesView,
                Permission::QueuesManage,
                Permission::AttendancesView,
                Permission::AttendancesCreate,
                Permission::AttendancesUpdateStatus,
                Permission::AttendancesResolve,
                Permission::AttendancesCancel,
                Permission::AttendancesAssign,
            ],
            Role::Supervisor => [
                Permission::AclView,
                Permission::UsersView,
                Permission::QueuesView,
                Permission::AttendancesView,
                Permission::AttendancesCreate,
                Permission::AttendancesUpdateStatus,
                Permission::AttendancesResolve,
                Permission::AttendancesAssign,
            ],
            Role::Operator => [
                Permission::QueuesView,
                Permission::AttendancesView,
                Permission::AttendancesCreate,
                Permission::AttendancesUpdateStatus,
            ],
            Role::Viewer => [
                Permission::QueuesView,
                Permission::AttendancesView,
            ],
        };
    }

    public static function hasPermission(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::permissionsForRole($role), true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function roleDefinitions(): array
    {
        return array_map(
            fn (Role $role): array => [
                'key' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
                'permissions' => array_map(
                    fn (Permission $permission): array => self::permissionDefinition($permission),
                    self::permissionsForRole($role),
                ),
            ],
            Role::cases(),
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function permissionDefinitions(): array
    {
        return array_map(
            fn (Permission $permission): array => self::permissionDefinition($permission),
            Permission::cases(),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function permissionDefinition(Permission $permission): array
    {
        return [
            'key' => $permission->value,
            'label' => $permission->label(),
            'description' => $permission->description(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function manageableRoleDefinitions(): array
    {
        return array_map(
            fn (Role $role): array => [
                'key' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
            ],
            Role::cases(),
        );
    }
}
