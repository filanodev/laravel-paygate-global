<?php

namespace PayGate\LaravelPayGateGlobal\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class PayGateGlobalService
{
    protected $client;
    protected $authToken;
    
    const BASE_URL = 'https://paygateglobal.com/api/v1';
    const PAYMENT_PAGE_URL = 'https://paygateglobal.com/v1/page';

    public function __construct()
    {
        $this->client = new Client();
        $this->authToken = config('paygate-global.auth_token');
        
        if (!$this->authToken) {
            throw new \InvalidArgumentException('PayGateGlobal: auth_token est requis. Vérifiez votre configuration.');
        }
    }

    public function initiatePayment(array $params): array
    {
        $requiredParams = ['phone_number', 'amount', 'identifier', 'network'];
        $this->validateRequiredParams($params, $requiredParams);

        $data = [
            'auth_token' => $this->authToken,
            'phone_number' => $params['phone_number'],
            'amount' => $params['amount'],
            'identifier' => $params['identifier'],
            'network' => strtoupper($params['network']),
        ];

        if (isset($params['description'])) {
            $data['description'] = $params['description'];
        }

        return $this->makeRequest('POST', '/pay', $data);
    }

    public function generatePaymentUrl(array $params): string
    {
        $requiredParams = ['amount', 'identifier'];
        $this->validateRequiredParams($params, $requiredParams);

        $queryParams = [
            'token' => $this->authToken,
            'amount' => $params['amount'],
            'identifier' => $params['identifier'],
        ];

        $optionalParams = ['description', 'phone', 'network'];
        foreach ($optionalParams as $param) {
            if (isset($params[$param])) {
                $queryParams[$param] = $params[$param];
            }
        }

        // URLs de redirection dynamiques
        if (isset($params['success_url'])) {
            $queryParams['url'] = $params['success_url'];
        } elseif (isset($params['return_url'])) {
            $queryParams['url'] = $params['return_url'];
        }

        return self::PAYMENT_PAGE_URL . '?' . http_build_query($queryParams);
    }

    public function getCallbackUrl(): string
    {
        $configUrl = config('paygate-global.callback_url');
        
        if ($configUrl) {
            return $configUrl;
        }
        
        return url('paygate-global/webhook');
    }

    public function checkPaymentStatus(string $txReference): array
    {
        $data = [
            'auth_token' => $this->authToken,
            'tx_reference' => $txReference,
        ];

        return $this->makeRequest('POST', '/status', $data);
    }

    public function checkPaymentStatusByIdentifier(string $identifier): array
    {
        $data = [
            'auth_token' => $this->authToken,
            'identifier' => $identifier,
        ];

        return $this->makeRequest('POST', '/v2/status', $data);
    }

    public function checkBalance(): array
    {
        $data = [
            'auth_token' => $this->authToken,
        ];

        return $this->makeRequest('POST', '/check-balance', $data);
    }

    public function disburse(array $params): array
    {
        $requiredParams = ['phone_number', 'amount', 'reason', 'network'];
        $this->validateRequiredParams($params, $requiredParams);

        $data = [
            'auth_token' => $this->authToken,
            'phone_number' => $params['phone_number'],
            'amount' => $params['amount'],
            'reason' => $params['reason'],
            'network' => strtoupper($params['network']),
        ];

        if (isset($params['reference'])) {
            $data['reference'] = $params['reference'];
        }

        return $this->makeRequest('POST', '/disburse', $data);
    }

    public function validateWebhookSignature(array $payload, string $signature = null): bool
    {
        if (!$signature && isset($_SERVER['HTTP_X_PAYGATE_SIGNATURE'])) {
            $signature = $_SERVER['HTTP_X_PAYGATE_SIGNATURE'];
        }

        if (!$signature) {
            return false;
        }

        $webhookSecret = config('paygate-global.webhook_secret');
        if (!$webhookSecret) {
            Log::warning('PayGateGlobal: Webhook secret not configured');
            return false;
        }

        $expectedSignature = hash_hmac('sha256', json_encode($payload), $webhookSecret);
        return hash_equals($signature, $expectedSignature);
    }

    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = self::BASE_URL . $endpoint;
            
            $response = $this->client->request($method, $url, [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => config('paygate-global.timeout', 30),
            ]);

            $body = $response->getBody()->getContents();
            return json_decode($body, true) ?? [];

        } catch (RequestException $e) {
            $errorMessage = 'PayGateGlobal API Error: ' . $e->getMessage();
            
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();
                $errorMessage .= ' Response: ' . $body;
                
                Log::error($errorMessage, [
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $body,
                    'request_data' => $data,
                ]);

                $responseData = json_decode($body, true);
                if ($responseData) {
                    return $responseData;
                }
            } else {
                Log::error($errorMessage, ['request_data' => $data]);
            }

            throw new \Exception($errorMessage);
        }
    }

    protected function validateRequiredParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new \InvalidArgumentException("Le paramètre '{$param}' est requis.");
            }
        }
    }

    public function getStatusMessage(int $statusCode): string
    {
        $statuses = [
            0 => 'Paiement réussi avec succès',
            2 => 'En cours',
            4 => 'Expiré',
            6 => 'Annulé',
        ];

        return $statuses[$statusCode] ?? 'Statut inconnu';
    }

    public function getTransactionStatusMessage(int $statusCode): string
    {
        $statuses = [
            0 => 'Transaction enregistrée avec succès',
            2 => 'Jeton d\'authentification invalide',
            4 => 'Paramètres invalides',
            6 => 'Doublons détectés. Une transaction avec le même identifiant existe déjà.',
        ];

        return $statuses[$statusCode] ?? 'Statut de transaction inconnu';
    }
}