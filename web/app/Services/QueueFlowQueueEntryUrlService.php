<?php

namespace App\Services;

use LogicException;

final class QueueFlowQueueEntryUrlService
{
    public function forService(int $businessId, int $branchId, int $serviceId): string
    {
        $baseUrl = rtrim((string) config('services.queueflow.public_web_url'), '/');
        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
        $host = rtrim(strtolower((string) parse_url($baseUrl, PHP_URL_HOST)), '.');
        $containsCredentials = parse_url($baseUrl, PHP_URL_USER) !== null
            || parse_url($baseUrl, PHP_URL_PASS) !== null;
        $containsQueryOrFragment = parse_url($baseUrl, PHP_URL_QUERY) !== null
            || parse_url($baseUrl, PHP_URL_FRAGMENT) !== null;

        $path = (string) parse_url($baseUrl, PHP_URL_PATH);

        if ($scheme !== 'https'
            || $host === ''
            || $containsCredentials
            || $containsQueryOrFragment
            || ! in_array($path, ['', '/'], true)
            || $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || str_starts_with($host, '127.')
            || in_array($host, ['0.0.0.0', '::1'], true)) {
            throw new LogicException(
                'QUEUEFLOW_PUBLIC_URL must be a publicly reachable HTTPS origin without credentials, a path, a query, or a fragment before a queue entry QR can be shown.',
            );
        }

        return $baseUrl.route('services.show', [
            'businessId' => $businessId,
            'branchId' => $branchId,
            'serviceId' => $serviceId,
        ], absolute: false);
    }
}
