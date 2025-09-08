<?php

namespace PayGate\LaravelPayGateGlobal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use PayGate\LaravelPayGateGlobal\Events\PaymentReceived;
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('PayGateGlobal Webhook received', $payload);

            $signature = $request->header('X-PayGate-Signature');
            
            if (config('paygate-global.webhook_secret')) {
                if (!PayGateGlobal::validateWebhookSignature($payload, $signature)) {
                    Log::warning('PayGateGlobal: Invalid webhook signature', [
                        'payload' => $payload,
                        'signature' => $signature,
                    ]);
                    
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
            }

            $requiredFields = [
                'tx_reference',
                'identifier', 
                'amount',
                'datetime',
                'payment_method',
                'phone_number'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($payload[$field])) {
                    Log::error("PayGateGlobal Webhook: Missing required field '{$field}'", $payload);
                    return response()->json(['error' => "Missing field: {$field}"], 400);
                }
            }

            Event::dispatch(new PaymentReceived(
                $payload['tx_reference'],
                $payload['identifier'],
                $payload['payment_reference'] ?? null,
                (float) $payload['amount'],
                $payload['datetime'],
                $payload['payment_method'],
                $payload['phone_number']
            ));

            Log::info('PayGateGlobal: Payment received event dispatched', [
                'tx_reference' => $payload['tx_reference'],
                'identifier' => $payload['identifier'],
                'amount' => $payload['amount'],
            ]);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('PayGateGlobal Webhook Error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}