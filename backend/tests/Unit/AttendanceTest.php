<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class AttendanceTest extends TestCase
{
    public function test_it_identifies_terminal_statuses(): void
    {
        $resolved = new Attendance([
            'status' => AttendanceStatus::RESOLVED,
        ]);

        $cancelled = new Attendance([
            'status' => AttendanceStatus::CANCELLED,
        ]);

        $open = new Attendance([
            'status' => AttendanceStatus::OPEN,
        ]);

        $this->assertTrue($resolved->isTerminal());
        $this->assertTrue($cancelled->isTerminal());
        $this->assertFalse($open->isTerminal());
    }

    public function test_it_allows_status_update_when_attendance_is_unassigned_or_belongs_to_the_same_user(): void
    {
        $user = new User();
        $user->id = 42;

        $unassignedAttendance = new Attendance([
            'assigned_to' => null,
        ]);

        $assignedToSameUser = new Attendance([
            'assigned_to' => 42,
        ]);

        $assignedToAnotherUser = new Attendance([
            'assigned_to' => 99,
        ]);

        $this->assertTrue($unassignedAttendance->isAssignableTo($user));
        $this->assertTrue($assignedToSameUser->isAssignableTo($user));
        $this->assertFalse($assignedToAnotherUser->isAssignableTo($user));
    }

    public function test_it_exposes_only_operational_statuses_for_queue_queries(): void
    {
        $this->assertSame([
            AttendanceStatus::OPEN,
            AttendanceStatus::IN_PROGRESS,
            AttendanceStatus::WAITING_EXTERNAL,
        ], Attendance::operationalStatuses());
    }
}
