<?php

namespace Tests\Unit\Http\Requests\Staff;

use App\Http\Requests\Staff\QueueEntryMutationRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueueEntryMutationRequestTest extends TestCase
{
    public function test_canonical_uuid_passes_validation(): void
    {
        $validator = Validator::make(
            ['idempotency_key' => '11111111-1111-4111-8111-111111111111'],
            (new QueueEntryMutationRequest)->rules(),
        );

        $this->assertTrue($validator->passes());
    }

    #[DataProvider('invalidIdempotencyKeys')]
    public function test_invalid_idempotency_keys_fail_validation(array $payload): void
    {
        $validator = Validator::make(
            $payload,
            (new QueueEntryMutationRequest)->rules(),
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('idempotency_key'));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidIdempotencyKeys(): array
    {
        return [
            'missing' => [[]],
            'malformed' => [['idempotency_key' => 'not-a-uuid']],
            'uppercase' => [['idempotency_key' => '11111111-1111-4111-8111-AAAAAAAAAAAA']],
            'without dashes' => [['idempotency_key' => '11111111111141118111111111111111']],
            'surrounded by spaces' => [['idempotency_key' => ' 11111111-1111-4111-8111-111111111111 ']],
        ];
    }
}
