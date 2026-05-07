<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedNetworkMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('trusted_networks.enabled', true);
        config()->set('trusted_networks.allowed', [
            '127.0.0.1',
            '::1',
            '198.51.100.0/24',
        ]);
    }

    public function test_requests_from_an_allowed_ip_can_access_the_app(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.25'])
            ->get('/login');

        $response->assertOk();
    }

    public function test_requests_from_an_untrusted_ip_are_blocked_with_html_response(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('/login');

        $response
            ->assertStatus(403)
            ->assertSee('This ERP is available only from the company network or VPN.');
    }

    public function test_api_requests_from_an_untrusted_ip_receive_json(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/user');

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'This ERP is available only from the company network or VPN.',
            ]);
    }
}
