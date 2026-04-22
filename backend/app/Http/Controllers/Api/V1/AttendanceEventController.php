<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceEventResource;
use App\Models\Attendance;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceEventController extends Controller
{
    public function index(int $attendance, Request $request): AnonymousResourceCollection
    {
        return AttendanceEventResource::collection(
            $this->findAttendanceForTenant($attendance, $request->user()->tenant_id)
                ->events()
                ->latest('created_at')
                ->get()
        );
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
