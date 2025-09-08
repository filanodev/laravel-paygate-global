<?php

namespace PayGate\LaravelPayGateGlobal\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use PayGate\LaravelPayGateGlobal\Events\PaymentReceived;
use PayGate\LaravelPayGateGlobal\Providers\PayGateGlobalServiceProvider;

class WebhookTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [PayGateGlobalServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['paygate-global.auth_token' => 'test-token-123']);
    }

    public function test_webhook_handles_payment_notification(): void
    {
        Event::fake();

        $payload = [
            'tx_reference' => 'TXN123456',
            'identifier' => 'ORDER123',
            'payment_reference' => 'FLOOZ123456',
            'amount' => 1000,
            'datetime' => '2024-01-01 12:00:00',
            'payment_method' => 'FLOOZ',
            'phone_number' => '+22890123456'
        ];

        $response = $this->postJson('/paygate-global/webhook', $payload);

        $response->assertStatus(200)
                ->assertJson(['status' => 'success']);

        Event::assertDispatched(PaymentReceived::class, function ($event) {
            return $event->txReference === 'TXN123456' &&
                   $event->identifier === 'ORDER123' &&
                   $event->amount === 1000.0;
        });
    }

    public function test_webhook_rejects_invalid_payload(): void
    {
        $payload = [
            'tx_reference' => 'TXN123456',
        ];

        $response = $this->postJson('/paygate-global/webhook', $payload);

        $response->assertStatus(400);
    }

    public function test_webhook_validates_signature_when_configured(): void
    {
        config(['paygate-global.webhook_secret' => 'secret123']);

        $payload = [
            'tx_reference' => 'TXN123456',
            'identifier' => 'ORDER123',
            'payment_reference' => 'FLOOZ123456',
            'amount' => 1000,
            'datetime' => '2024-01-01 12:00:00',
            'payment_method' => 'FLOOZ',
            'phone_number' => '+22890123456'
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'secret123');

        $response = $this->postJson('/paygate-global/webhook', $payload, [
            'X-PayGate-Signature' => $signature
        ]);

        $response->assertStatus(200);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['paygate-global.webhook_secret' => 'secret123']);

        $payload = [
            'tx_reference' => 'TXN123456',
            'identifier' => 'ORDER123',
            'payment_reference' => 'FLOOZ123456',
            'amount' => 1000,
            'datetime' => '2024-01-01 12:00:00',
            'payment_method' => 'FLOOZ',
            'phone_number' => '+22890123456'
        ];

        $response = $this->postJson('/paygate-global/webhook', $payload, [
            'X-PayGate-Signature' => 'invalid-signature'
        ]);

        $response->assertStatus(401);
    }
}