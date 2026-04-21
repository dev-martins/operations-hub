<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAttendanceAssignmentRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;

class AttendanceAssignmentController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function update(UpdateAttendanceAssignmentRequest $request, Attendance $attendance): AttendanceResource
    {
        $attendance = $this->attendanceService->assign(
            $attendance,
            $request->integer('assigned_to'),
        );

        return AttendanceResource::make($attendance);
    }
}
