<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveQueueEntryCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $compact = mb_strtoupper((string) preg_replace(
            '/[\s-]+/u',
            '',
            trim((string) $this->input('entryCode')),
        ));

        $this->merge([
            'entryCode' => strlen($compact) === 14
                ? substr($compact, 0, 2).'-'.substr($compact, 2, 4).'-'.substr($compact, 6, 4).'-'.substr($compact, 10, 4)
                : $compact,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'entryCode' => ['required', 'string', 'regex:/\AQF-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}\z/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'entryCode.required' => 'Enter the queue code shown at the counter.',
            'entryCode.regex' => 'Enter a QueueFlow code in the format QF-XXXX-XXXX-XXXX.',
        ];
    }

    public function entryCode(): string
    {
        return mb_strtoupper((string) preg_replace(
            '/[\s-]+/u',
            '',
            trim((string) $this->validated('entryCode')),
        ));
    }
}
