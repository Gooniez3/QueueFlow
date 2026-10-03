<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerQueueEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'businessId' => ['required', 'integer', 'min:1'],
            'branchId' => ['required', 'integer', 'min:1'],
            'serviceId' => ['required', 'integer', 'min:1'],
        ];
    }
}
