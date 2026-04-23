<?php

namespace App\Http\Requests;

use App\Models\OperationQueue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var OperationQueue|null $queue */
        $queue = $this->route('queue');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('queues', 'code')
                    ->ignore($queue?->id)
                    ->where(fn ($query) => $query->where('tenant_id', $this->user()?->tenant_id)),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
