<?php

namespace App\Http\Requests\Staff;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQueueRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'queueType' => ['required', Rule::in(['service', 'shared'])],
            'serviceId' => [
                'nullable',
                'integer',
                'required_if:queueType,service',
                'prohibited_if:queueType,shared',
            ],
            'name' => ['required', 'string', 'max:150'],
            'ticketPrefix' => ['required', 'string', 'max:10'],
        ];
    }
}
