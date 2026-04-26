<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use App\Support\Acl\Permission;
use Illuminate\Auth\Access\Response;

class AttendancePolicy
{
    public function view(User $user, Attendance $attendance): Response
    {
        if ($attendance->tenant_id !== $user->tenant_id) {
            return Response::deny('Atendimento fora do tenant autenticado.');
        }

        if (! $user->hasPermission(Permission::AttendancesView)) {
            return Response::deny('Usuário sem permissão para visualizar este atendimento.');
        }

        return Response::allow();
    }

    public function updateStatus(User $user, Attendance $attendance): Response
    {
        $tenantCheck = $this->assertTenant($user, $attendance);
        if ($tenantCheck !== null) {
            return $tenantCheck;
        }

        if (! $user->hasPermission(Permission::AttendancesUpdateStatus)) {
            return Response::deny('Usuário sem permissão para atualizar status de atendimentos.');
        }

        if ($attendance->isTerminal()) {
            return Response::deny('Atendimentos encerrados não podem voltar para fluxo operacional.');
        }

        if ($user->hasPermission(Permission::AttendancesResolve) || $attendance->isAssignableTo($user)) {
            return Response::allow();
        }

        return Response::deny('Este atendimento só pode ser atualizado pelo operador responsável ou pela supervisão.');
    }

    public function resolve(User $user, Attendance $attendance): Response
    {
        $tenantCheck = $this->assertTenant($user, $attendance);
        if ($tenantCheck !== null) {
            return $tenantCheck;
        }

        if (! $user->hasPermission(Permission::AttendancesResolve)) {
            return Response::deny('Usuário sem permissão para resolver atendimentos.');
        }

        if ($attendance->isTerminal()) {
            return Response::deny('Atendimentos encerrados não podem ser resolvidos novamente.');
        }

        return Response::allow();
    }

    public function cancel(User $user, Attendance $attendance): Response
    {
        $tenantCheck = $this->assertTenant($user, $attendance);
        if ($tenantCheck !== null) {
            return $tenantCheck;
        }

        if (! $user->hasPermission(Permission::AttendancesCancel)) {
            return Response::deny('Usuário sem permissão para cancelar atendimentos.');
        }

        if ($attendance->isTerminal()) {
            return Response::deny('Atendimentos encerrados não podem ser cancelados novamente.');
        }

        return Response::allow();
    }

    public function assign(User $user, Attendance $attendance): Response
    {
        $tenantCheck = $this->assertTenant($user, $attendance);
        if ($tenantCheck !== null) {
            return $tenantCheck;
        }

        if (! $user->hasPermission(Permission::AttendancesAssign)) {
            return Response::deny('Usuário sem permissão para atribuir atendimentos.');
        }

        if ($attendance->isTerminal()) {
            return Response::deny('Atendimentos encerrados não podem ser reatribuídos.');
        }

        return Response::allow();
    }

    private function assertTenant(User $user, Attendance $attendance): ?Response
    {
        if ($attendance->tenant_id !== $user->tenant_id) {
            return Response::deny('Atendimento fora do tenant autenticado.');
        }

        return null;
    }
}
