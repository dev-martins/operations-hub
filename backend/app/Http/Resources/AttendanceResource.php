<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'protocol' => $this->protocol,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'origin' => $this->origin?->value,
            'origin_label' => $this->origin?->label(),
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'resolution_notes' => $this->resolution_notes,
            'queue' => $this->whenLoaded('queue', function (): array {
                return [
                    'id' => $this->queue?->id,
                    'name' => $this->queue?->name,
                    'code' => $this->queue?->code,
                ];
            }),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'assigned_to' => $this->assigned_to,
            'created_by' => $this->created_by,
            'opened_at' => optional($this->opened_at)?->toIso8601String(),
            'first_response_at' => optional($this->first_response_at)?->toIso8601String(),
            'resolved_at' => optional($this->resolved_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'events' => AttendanceEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
