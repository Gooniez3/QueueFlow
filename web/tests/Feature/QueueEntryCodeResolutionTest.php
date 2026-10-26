<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueueEntryCodeResolutionTest extends TestCase
{
    public function test_formatted_lowercase_code_resolves_to_service_preview(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/services/resolve-entry-code/QFABCDEFGHJKLM' => Http::response([
                'entryCode' => 'QF-ABCD-EFGH-JKLM',
                'businessId' => 10,
                'branchId' => 21,
                'serviceId' => 31,
            ]),
        ]);

        $this->post(route('scanner.entry-code.resolve'), [
            'entryCode' => '  qf abcd-efgh jklm  ',
        ])->assertRedirect(route('services.show', [10, 21, 31]));

        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'http://localhost:8080/api/v1/public/services/resolve-entry-code/QFABCDEFGHJKLM');
    }

    public function test_unsupported_characters_are_rejected_before_calling_the_api(): void
    {
        Http::fake();

        $this->from(route('scanner.show'))
            ->post(route('scanner.entry-code.resolve'), [
                'entryCode' => 'QF-ABCD-EFGH-JKLO',
            ])
            ->assertRedirect(route('scanner.show'))
            ->assertSessionHasErrors('entryCode');

        Http::assertNothingSent();
    }

    #[DataProvider('upstreamErrorProvider')]
    public function test_upstream_errors_return_safe_form_feedback(int $status, string $message): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Internal upstream detail'], $status),
        ]);

        $this->from(route('scanner.show'))
            ->post(route('scanner.entry-code.resolve'), [
                'entryCode' => 'QF-ABCD-EFGH-JKLM',
            ])
            ->assertRedirect(route('scanner.show'))
            ->assertSessionHasErrors(['entryCode' => $message]);
    }

    public function test_api_connection_failure_returns_safe_form_feedback(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->from(route('scanner.show'))
            ->post(route('scanner.entry-code.resolve'), [
                'entryCode' => 'QF-ABCD-EFGH-JKLM',
            ])
            ->assertRedirect(route('scanner.show'))
            ->assertSessionHasErrors([
                'entryCode' => 'QueueFlow could not check that code right now. Please try again.',
            ]);
    }

    /** @return array<string, array{int, string}> */
    public static function upstreamErrorProvider(): array
    {
        return [
            'not found' => [404, 'That queue code is invalid. Check the code and try again.'],
            'inactive' => [409, 'That service is currently unavailable.'],
            'server error' => [500, 'QueueFlow could not check that code right now. Please try again.'],
        ];
    }
}
