<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QueueResource;
use App\Models\OperationQueue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QueueController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $queues = OperationQueue::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->withCount('attendances')
            ->orderBy('name')
            ->get();

        return QueueResource::collection($queues);
    }
}
