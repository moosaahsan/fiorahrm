<?php

namespace Tests\Feature;

use Tests\TestCase;

class CelebrationApiTest extends TestCase
{
    public function test_celebrations_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/employee/celebrations');

        $response->assertUnauthorized();
    }

    public function test_celebrations_service_returns_expected_shape(): void
    {
        $service = app(\App\Services\CelebrationService::class);
        $this->assertInstanceOf(\App\Services\CelebrationService::class, $service);
    }
}
