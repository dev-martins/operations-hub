<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceEventResource;
use App\Models\Attendance;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceEventController extends Controller
{
    public function index(Attendance $attendance): AnonymousResourceCollection
    {
        return AttendanceEventResource::collection(
            $attendance->events()->latest('created_at')->get()
        );
    }
}
