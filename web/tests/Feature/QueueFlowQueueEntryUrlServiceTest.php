<?php

namespace Tests\Feature;

use App\Services\QueueFlowQueueEntryUrlService;
use LogicException;
use Tests\TestCase;

class QueueFlowQueueEntryUrlServiceTest extends TestCase
{
    public function test_builds_service_url_from_https_root_origin(): void
    {
        config()->set('services.queueflow.public_web_url', 'https://queueflow.example/');

        $url = app(QueueFlowQueueEntryUrlService::class)->forService(10, 21, 31);

        $this->assertSame(
            'https://queueflow.example/businesses/10/branches/21/services/31',
            $url,
        );
    }

    public function test_rejects_invalid_or_unsupported_public_urls(): void
    {
        $invalidUrls = [
            'queueflow.example',
            'https:///missing-host',
            'http://queueflow.example',
            'ftp://queueflow.example',
            'https://localhost',
            'https://127.0.0.1',
            'https://user:password@queueflow.example',
            'https://queueflow.example?preview=1',
            'https://queueflow.example#preview',
            'https://queueflow.example/queueflow',
        ];

        foreach ($invalidUrls as $invalidUrl) {
            config()->set('services.queueflow.public_web_url', $invalidUrl);

            try {
                app(QueueFlowQueueEntryUrlService::class)->forService(10, 21, 31);
                $this->fail("Expected {$invalidUrl} to be rejected.");
            } catch (LogicException $exception) {
                $this->assertStringContainsString('publicly reachable HTTPS origin', $exception->getMessage());
            }
        }
    }
}
