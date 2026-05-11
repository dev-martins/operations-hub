<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Support\Cache\OperationalAttendanceListCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly OperationalAttendanceListCache $operationalAttendanceListCache,
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = $this->buildIndexQuery($request);

        if ($this->shouldUseOperationalListCache($request)) {
            $payload = $this->operationalAttendanceListCache->rememberForTenantPage(
                $request->user()->tenant_id,
                max(1, $request->integer('page', 1)),
                fn (): array => AttendanceResource::collection($query->paginate(10))->response()->getData(true),
            );

            return response()->json($payload);
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

    private function buildIndexQuery(Request $request): Builder
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

        return $query;
    }

    private function shouldUseOperationalListCache(Request $request): bool
    {
        return $request->boolean('operational_only')
            && ! $request->filled('status')
            && ! $request->filled('priority')
            && ! $request->filled('queue_id');
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
