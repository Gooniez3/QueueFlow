<?php

namespace App\Services;

use App\Data\BusinessData;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class QueueFlowApiClient
{
    public function client(): PendingRequest
    {
        return Http::baseUrl(
            config('services.queueflow.base_url')
        )->acceptJson();
    }

    /**
     * @return list<BusinessData>
     */
    public function businesses(): array
    {
        $response = $this->client()
            ->get('/api/v1/businesses')
            ->throw();

        return collect($response->json())
            ->map(fn (array $business) => BusinessData::fromArray($business))
            ->all();
    }

    public function business(int $id): BusinessData
    {
        $response = $this->client()
            ->get("/api/v1/businesses/{$id}")
            ->throw();

        return BusinessData::fromArray($response->json());
    }

    public function createBusiness(
        string $name,
        ?string $description = null
    ): BusinessData {
        $response = $this->client()
            ->post('/api/v1/businesses', [
                'name' => $name,
                'description' => $description,
            ])
            ->throw();

        return BusinessData::fromArray($response->json());
    }
}
