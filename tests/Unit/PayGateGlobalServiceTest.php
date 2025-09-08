<?php

namespace PayGate\LaravelPayGateGlobal\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use PayGate\LaravelPayGateGlobal\Providers\PayGateGlobalServiceProvider;
use PayGate\LaravelPayGateGlobal\Services\PayGateGlobalService;

class PayGateGlobalServiceTest extends TestCase
{
    protected $service;
    protected $mockHandler;

    protected function getPackageProviders($app): array
    {
        return [PayGateGlobalServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['paygate-global.auth_token' => 'test-token-123']);
        
        $this->service = new PayGateGlobalService();
        $this->mockHandler = new MockHandler();
    }

    protected function mockHttpClient(array $responses): void
    {
        $stack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $stack]);
        
        foreach ($responses as $response) {
            $this->mockHandler->append($response);
        }
        
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $client);
    }

    public function test_initiate_payment_success(): void
    {
        $this->mockHttpClient([
            new Response(200, [], json_encode([
                'tx_reference' => 'TXN123456',
                'status' => 0
            ]))
        ]);

        $result = $this->service->initiatePayment([
            'phone_number' => '+22890123456',
            'amount' => 1000,
            'identifier' => 'ORDER123',
            'network' => 'FLOOZ',
            'description' => 'Test payment'
        ]);

        $this->assertEquals('TXN123456', $result['tx_reference']);
        $this->assertEquals(0, $result['status']);
    }

    public function test_initiate_payment_missing_required_params(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->initiatePayment([
            'phone_number' => '+22890123456',
            'amount' => 1000,
        ]);
    }

    public function test_constructor_throws_exception_without_auth_token(): void
    {
        config(['paygate-global.auth_token' => null]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PayGateGlobal: auth_token est requis');
        
        new PayGateGlobalService();
    }

    public function test_generate_payment_url(): void
    {
        $url = $this->service->generatePaymentUrl([
            'amount' => 1000,
            'identifier' => 'ORDER123',
            'description' => 'Test payment',
            'phone' => '+22890123456',
            'success_url' => 'https://example.com/success'
        ]);

        $this->assertStringContains('paygateglobal.com/v1/page', $url);
        $this->assertStringContains('token=test-token-123', $url);
        $this->assertStringContains('amount=1000', $url);
        $this->assertStringContains('identifier=ORDER123', $url);
        $this->assertStringContains('description=Test+payment', $url);
        $this->assertStringContains('url=https%3A%2F%2Fexample.com%2Fsuccess', $url);
    }

    public function test_generate_payment_url_with_return_url(): void
    {
        $url = $this->service->generatePaymentUrl([
            'amount' => 1000,
            'identifier' => 'ORDER123',
            'return_url' => 'https://example.com/callback'
        ]);

        $this->assertStringContains('url=https%3A%2F%2Fexample.com%2Fcallback', $url);
    }

    public function test_check_payment_status(): void
    {
        $this->mockHttpClient([
            new Response(200, [], json_encode([
                'tx_reference' => 'TXN123456',
                'status' => 0,
                'payment_method' => 'FLOOZ'
            ]))
        ]);

        $result = $this->service->checkPaymentStatus('TXN123456');

        $this->assertEquals('TXN123456', $result['tx_reference']);
        $this->assertEquals(0, $result['status']);
    }

    public function test_check_balance(): void
    {
        $this->mockHttpClient([
            new Response(200, [], json_encode([
                'flooz' => 50000,
                'tmoney' => 25000
            ]))
        ]);

        $result = $this->service->checkBalance();

        $this->assertEquals(50000, $result['flooz']);
        $this->assertEquals(25000, $result['tmoney']);
    }

    public function test_disburse(): void
    {
        $this->mockHttpClient([
            new Response(200, [], json_encode([
                'tx_reference' => 'DISB123456',
                'status' => 200
            ]))
        ]);

        $result = $this->service->disburse([
            'phone_number' => '+22890123456',
            'amount' => 1000,
            'reason' => 'Remboursement commande ORDER123',
            'network' => 'FLOOZ'
        ]);

        $this->assertEquals('DISB123456', $result['tx_reference']);
        $this->assertEquals(200, $result['status']);
    }

    public function test_webhook_signature_validation(): void
    {
        config(['paygate-global.webhook_secret' => 'secret123']);
        
        $payload = [
            'tx_reference' => 'TXN123456',
            'amount' => 1000,
            'identifier' => 'ORDER123'
        ];
        
        $signature = hash_hmac('sha256', json_encode($payload), 'secret123');
        
        $isValid = $this->service->validateWebhookSignature($payload, $signature);
        
        $this->assertTrue($isValid);
    }

    public function test_get_status_messages(): void
    {
        $this->assertEquals('Paiement réussi avec succès', $this->service->getStatusMessage(0));
        $this->assertEquals('En cours', $this->service->getStatusMessage(2));
        $this->assertEquals('Expiré', $this->service->getStatusMessage(4));
        $this->assertEquals('Annulé', $this->service->getStatusMessage(6));
        $this->assertEquals('Statut inconnu', $this->service->getStatusMessage(999));
    }

    public function test_get_transaction_status_messages(): void
    {
        $this->assertEquals('Transaction enregistrée avec succès', $this->service->getTransactionStatusMessage(0));
        $this->assertEquals('Jeton d\'authentification invalide', $this->service->getTransactionStatusMessage(2));
    }
}