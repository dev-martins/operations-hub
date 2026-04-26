<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAttendanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(AttendanceStatus::class)],
            'resolution_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                $this->input('status') === AttendanceStatus::RESOLVED->value
                && blank($this->input('resolution_notes'))
            ) {
                $validator->errors()->add(
                    'resolution_notes',
                    'O campo resolution_notes é obrigatório ao resolver um atendimento.'
                );
            }
        });
    }
}
