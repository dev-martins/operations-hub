<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAttendanceAssignmentRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceAssignmentController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function update(UpdateAttendanceAssignmentRequest $request, int $attendance): AttendanceResource
    {
        $record = $this->findAttendanceForTenant($attendance, $request->user()->tenant_id);
        $this->authorize('assign', $record);

        $attendance = $this->attendanceService->assign(
            $record,
            $request->integer('assigned_to'),
            $request->user(),
        );

        return AttendanceResource::make($attendance);
    }

    private function findAttendanceForTenant(int $attendanceId, int $tenantId): Attendance
    {
        return Attendance::query()
            ->whereKey($attendanceId)
            ->where('tenant_id', $tenantId)
            ->first()
            ?? throw new NotFoundHttpException;
    }
}
