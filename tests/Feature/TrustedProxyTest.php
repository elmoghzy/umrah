<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_loopback_proxy_uses_forwarded_https_scheme_when_generating_urls(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('/admin/login');

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/(?:href|src)="https:\/\/[^\"]+\/css\/filament\/filament\/app\.css/',
            $response->getContent(),
        );
    }

    public function test_untrusted_source_cannot_set_the_forwarded_https_scheme(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('/admin/login');

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/(?:href|src)="http:\/\/[^\"]+\/css\/filament\/filament\/app\.css/',
            $response->getContent(),
        );
        $this->assertDoesNotMatchRegularExpression(
            '/(?:href|src)="https:\/\/[^\"]+\/css\/filament\/filament\/app\.css/',
            $response->getContent(),
        );
    }
}
