<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPageRenderingTest extends TestCase
{
    #[DataProvider('statusPageProvider')]
    public function test_status_page_renders_its_code_and_accessible_illustration(int $status, string $label): void
    {
        $html = view("errors.{$status}")->render();

        $this->assertStringContainsString("data-error-code=\"{$status}\"", $html);
        $this->assertStringContainsString("HTTP status {$status}", $html);
        $this->assertStringContainsString($label, $html);
        $this->assertStringContainsString('Animated illustration for', $html);
        $this->assertStringContainsString('prefers-reduced-motion', $html);
    }

    public function test_laravel_uses_the_custom_page_for_a_real_not_found_response(): void
    {
        $this->get('/this-route-does-not-exist-error-page-check')
            ->assertNotFound()
            ->assertSee('data-error-code="404"', false)
            ->assertSee('This route is off the map.');
    }

    public function test_generic_error_families_use_the_branded_fallback(): void
    {
        foreach ([418, 501] as $status) {
            $html = view('errors.'.($status < 500 ? '4xx' : '5xx'), [
                'exception' => new HttpException($status),
            ])->render();

            $this->assertStringContainsString("data-error-code=\"{$status}\"", $html);
            $this->assertStringContainsString('Unexpected response', $html);
        }
    }

    /** @return array<string, array{int, string}> */
    public static function statusPageProvider(): array
    {
        return [
            'bad request' => [400, 'Bad request'],
            'authentication required' => [401, 'Authentication required'],
            'access denied' => [403, 'Access denied'],
            'not found' => [404, 'Page not found'],
            'method not allowed' => [405, 'Method not allowed'],
            'request timeout' => [408, 'Request timeout'],
            'session expired' => [419, 'Session expired'],
            'validation error' => [422, 'Validation error'],
            'rate limited' => [429, 'Too many requests'],
            'server error' => [500, 'Internal server error'],
            'bad gateway' => [502, 'Bad gateway'],
            'service unavailable' => [503, 'Service unavailable'],
            'gateway timeout' => [504, 'Gateway timeout'],
        ];
    }
}
