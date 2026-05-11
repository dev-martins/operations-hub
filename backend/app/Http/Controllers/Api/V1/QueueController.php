<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Http\Resources\QueueResource;
use App\Models\Attendance;
use App\Models\OperationQueue;
use App\Support\Cache\OperationalAttendanceListCache;
use App\Support\Cache\QueueOverviewCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueOverviewCache $queueOverviewCache,
        private readonly OperationalAttendanceListCache $operationalAttendanceListCache,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $queues = $this->queueOverviewCache->rememberForTenant($request->user()->tenant_id, function () use ($request): array {
            $queues = OperationQueue::query()
                ->where('tenant_id', $request->user()->tenant_id)
                ->withCount([
                    'attendances as attendances_count' => fn ($query) => $query->whereIn('status', Attendance::operationalStatuses()),
                ])
                ->orderBy('name')
                ->get();

            return QueueResource::collection($queues)->response()->getData(true)['data'];
        });

        return response()->json([
            'data' => $queues,
        ]);
    }

    public function store(StoreQueueRequest $request): QueueResource
    {
        $queue = OperationQueue::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->string('name')->toString(),
            'code' => strtoupper($request->string('code')->toString()),
            'description' => $request->input('description'),
            'active' => $request->boolean('active', true),
        ]);

        $this->queueOverviewCache->forgetForTenant($request->user()->tenant_id);
        $this->operationalAttendanceListCache->invalidateForTenant($request->user()->tenant_id);

        return new QueueResource($queue);
    }

    public function update(UpdateQueueRequest $request, OperationQueue $queue): QueueResource
    {
        abort_unless($queue->tenant_id === $request->user()->tenant_id, Response::HTTP_NOT_FOUND);

        $payload = $request->validated();

        if (array_key_exists('code', $payload)) {
            $payload['code'] = strtoupper((string) $payload['code']);
        }

        $queue->fill($payload)->save();

        $this->queueOverviewCache->forgetForTenant($request->user()->tenant_id);
        $this->operationalAttendanceListCache->invalidateForTenant($request->user()->tenant_id);

        return new QueueResource($queue->fresh());
    }
}
