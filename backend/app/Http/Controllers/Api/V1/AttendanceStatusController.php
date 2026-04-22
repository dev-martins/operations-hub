<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAttendanceStatusRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceStatusController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function update(UpdateAttendanceStatusRequest $request, int $attendance): AttendanceResource
    {
        $attendance = $this->attendanceService->changeStatus(
            $this->findAttendanceForTenant($attendance, $request->user()->tenant_id),
            AttendanceStatus::from($request->string('status')->toString()),
            $request->string('resolution_notes')->toString() ?: null,
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
            ?? throw new NotFoundHttpException();
    }
}
