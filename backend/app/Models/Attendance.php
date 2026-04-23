<?php

namespace App\Models;

use App\Enums\AttendanceOrigin;
use App\Enums\AttendancePriority;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'protocol',
        'title',
        'description',
        'type',
        'origin',
        'priority',
        'status',
        'queue_id',
        'assigned_to',
        'created_by',
        'resolution_notes',
        'opened_at',
        'first_response_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AttendanceType::class,
            'origin' => AttendanceOrigin::class,
            'priority' => AttendancePriority::class,
            'status' => AttendanceStatus::class,
            'opened_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(OperationQueue::class, 'queue_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class)->latest('created_at');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            AttendanceStatus::RESOLVED,
            AttendanceStatus::CANCELLED,
        ], true);
    }

    public function isAssignableTo(User $user): bool
    {
        return $this->assigned_to === null || $this->assigned_to === $user->id;
    }
}
