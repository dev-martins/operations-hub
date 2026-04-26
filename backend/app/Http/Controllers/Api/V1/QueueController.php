<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Http\Resources\QueueResource;
use App\Models\Attendance;
use App\Models\OperationQueue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class QueueController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $queues = OperationQueue::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->withCount([
                'attendances as attendances_count' => fn ($query) => $query->whereIn('status', Attendance::operationalStatuses()),
            ])
            ->orderBy('name')
            ->get();

        return QueueResource::collection($queues);
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

        return new QueueResource($queue->fresh());
    }
}
