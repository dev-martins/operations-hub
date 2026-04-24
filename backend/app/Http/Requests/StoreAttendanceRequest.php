<?php

namespace App\Http\Requests;

use App\Enums\AttendanceOrigin;
use App\Enums\AttendancePriority;
use App\Enums\AttendanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', new Enum(AttendanceType::class)],
            'origin' => ['required', new Enum(AttendanceOrigin::class)],
            'priority' => ['required', new Enum(AttendancePriority::class)],
            'queue_id' => [
                'required',
                'integer',
                Rule::exists('queues', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ];
    }
}
