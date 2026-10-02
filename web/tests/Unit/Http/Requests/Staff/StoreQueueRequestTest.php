<?php

namespace Tests\Unit\Http\Requests\Staff;

use App\Http\Requests\Staff\StoreQueueRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreQueueRequestTest extends TestCase
{
    #[DataProvider('validQueuePayloads')]
    public function test_valid_queue_payloads_pass_validation(array $payload): void
    {
        $validator = Validator::make(
            $payload,
            (new StoreQueueRequest)->rules(),
        );

        $this->assertTrue($validator->passes());
    }

    #[DataProvider('invalidQueuePayloads')]
    public function test_invalid_queue_payloads_fail_the_spring_contract_field(
        array $payload,
        string $field,
    ): void {
        $validator = Validator::make(
            $payload,
            (new StoreQueueRequest)->rules(),
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has($field));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function validQueuePayloads(): array
    {
        return [
            'shared branch queue' => [[
                'serviceId' => null,
                'name' => 'Walk-in Queue',
                'ticketPrefix' => 'A',
            ]],
            'service-specific queue' => [[
                'serviceId' => 31,
                'name' => 'General Consultation',
                'ticketPrefix' => 'GC',
            ]],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidQueuePayloads(): array
    {
        return [
            'non-integer service' => [[
                'serviceId' => 'general',
                'name' => 'Walk-in Queue',
                'ticketPrefix' => 'A',
            ], 'serviceId'],
            'missing name' => [[
                'serviceId' => null,
                'ticketPrefix' => 'A',
            ], 'name'],
            'name over 150 characters' => [[
                'serviceId' => null,
                'name' => str_repeat('Q', 151),
                'ticketPrefix' => 'A',
            ], 'name'],
            'missing ticket prefix' => [[
                'serviceId' => null,
                'name' => 'Walk-in Queue',
            ], 'ticketPrefix'],
            'ticket prefix over 10 characters' => [[
                'serviceId' => null,
                'name' => 'Walk-in Queue',
                'ticketPrefix' => 'ABCDEFGHIJK',
            ], 'ticketPrefix'],
        ];
    }
}
