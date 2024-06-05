<?php

namespace App\Http\Requests\Admi\Podium;

use Illuminate\Foundation\Http\FormRequest;

class ComputePodiumRequest extends FormRequest
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
            'ladder_id' => 'required|exists:ladders,id',
            'date_from' => 'required|date|date_format:Y-m-d|before:date_to',
            'time_from' => 'required|date_format:H:i',
            'date_to' => 'required|date|date_format:Y-m-d|after:date_from',
            'time_to' => 'required|date_format:H:i',
        ];
    }
}
