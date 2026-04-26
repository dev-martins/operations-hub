<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use App\Policies\AttendancePolicy;
use PHPUnit\Framework\TestCase;

class AttendancePolicyTest extends TestCase
{
    private AttendancePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AttendancePolicy;
    }

    public function test_operator_can_update_status_when_attendance_is_unassigned_in_the_same_tenant(): void
    {
        $user = new User([
            'tenant_id' => 10,
            'role' => 'operator',
        ]);

        $attendance = new Attendance([
            'tenant_id' => 10,
            'status' => AttendanceStatus::OPEN,
            'assigned_to' => null,
        ]);

        $response = $this->policy->updateStatus($user, $attendance);

        $this->assertTrue($response->allowed());
    }

    public function test_operator_cannot_update_status_for_attendance_assigned_to_another_user(): void
    {
        $user = new User([
            'id' => 7,
            'tenant_id' => 10,
            'role' => 'operator',
        ]);

        $attendance = new Attendance([
            'tenant_id' => 10,
            'status' => AttendanceStatus::IN_PROGRESS,
            'assigned_to' => 99,
        ]);

        $response = $this->policy->updateStatus($user, $attendance);

        $this->assertFalse($response->allowed());
        $this->assertSame(
            'Este atendimento só pode ser atualizado pelo operador responsável ou pela supervisão.',
            $response->message()
        );
    }

    public function test_supervisor_can_update_status_even_when_attendance_is_assigned_to_another_user(): void
    {
        $user = new User([
            'id' => 7,
            'tenant_id' => 10,
            'role' => 'supervisor',
        ]);

        $attendance = new Attendance([
            'tenant_id' => 10,
            'status' => AttendanceStatus::IN_PROGRESS,
            'assigned_to' => 99,
        ]);

        $response = $this->policy->updateStatus($user, $attendance);

        $this->assertTrue($response->allowed());
    }

    public function test_policy_denies_access_when_attendance_is_from_another_tenant(): void
    {
        $user = new User([
            'tenant_id' => 10,
            'role' => 'admin',
        ]);

        $attendance = new Attendance([
            'tenant_id' => 20,
            'status' => AttendanceStatus::OPEN,
        ]);

        $response = $this->policy->assign($user, $attendance);

        $this->assertFalse($response->allowed());
        $this->assertSame('Atendimento fora do tenant autenticado.', $response->message());
    }

    public function test_policy_blocks_assignment_for_terminal_attendances(): void
    {
        $user = new User([
            'tenant_id' => 10,
            'role' => 'supervisor',
        ]);

        $attendance = new Attendance([
            'tenant_id' => 10,
            'status' => AttendanceStatus::RESOLVED,
        ]);

        $response = $this->policy->assign($user, $attendance);

        $this->assertFalse($response->allowed());
        $this->assertSame('Atendimentos encerrados não podem ser reatribuídos.', $response->message());
    }
}
