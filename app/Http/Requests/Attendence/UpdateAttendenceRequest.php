<?php

namespace App\Http\Requests\Attendence;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entries' => 'required|array',
            'entries.*.user_id' => 'required|exists:users,id',
            'entries.*.id' => 'required|exists:attendences,id',
            'entries.*.timestamp' => 'required',
            'entries.*.type' => 'required|in:check in,check out',
        ];
    }
}
