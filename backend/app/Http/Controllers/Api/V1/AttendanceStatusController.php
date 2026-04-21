<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAttendanceStatusRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;

class AttendanceStatusController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function update(UpdateAttendanceStatusRequest $request, Attendance $attendance): AttendanceResource
    {
        $attendance = $this->attendanceService->changeStatus(
            $attendance,
            AttendanceStatus::from($request->string('status')->toString()),
            $request->string('resolution_notes')->toString() ?: null,
        );

        return AttendanceResource::make($attendance);
    }
}
