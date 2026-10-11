<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class VerifyQueueEntryQrRequest extends FormRequest
{
    protected $dontFlash = ['credential'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'string', 'max:255'],
        ];
    }

    public function credential(): string
    {
        return trim((string) $this->validated('credential'));
    }
}
