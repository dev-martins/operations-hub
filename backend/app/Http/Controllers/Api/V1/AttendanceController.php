<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attendance::query()
            ->with(['queue', 'assignee', 'creator'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest('opened_at');

        if ($request->boolean('operational_only')) {
            $query->whereIn('status', Attendance::operationalStatuses());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('queue_id')) {
            $query->where('queue_id', $request->integer('queue_id'));
        }

        return AttendanceResource::collection($query->paginate(10));
    }

    public function store(StoreAttendanceRequest $request): AttendanceResource
    {
        $attendance = $this->attendanceService->create($request->validated(), $request->user());

        return AttendanceResource::make($attendance);
    }

    public function show(int $attendance, Request $request): AttendanceResource
    {
        $record = $this->findAttendanceForTenant($attendance, $request->user()->tenant_id);
        $this->authorize('view', $record);

        return AttendanceResource::make(
            $record->load(['queue', 'events', 'assignee', 'creator'])
        );
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
