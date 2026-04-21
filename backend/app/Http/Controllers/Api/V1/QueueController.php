<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QueueResource;
use App\Models\OperationQueue;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QueueController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $queues = OperationQueue::query()
            ->withCount('attendances')
            ->orderBy('name')
            ->get();

        return QueueResource::collection($queues);
    }
}
