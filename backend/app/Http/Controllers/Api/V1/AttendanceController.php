<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attendance::query()
            ->with('queue')
            ->latest('opened_at');

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
        $attendance = $this->attendanceService->create($request->validated());

        return AttendanceResource::make($attendance);
    }

    public function show(Attendance $attendance): AttendanceResource
    {
        return AttendanceResource::make($attendance->load(['queue', 'events']));
    }
}
