<?php

namespace PayGate\LaravelPayGateGlobal\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

class VerifyPayGateWebhook
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('paygate-global.webhook_secret')) {
            Log::warning('PayGateGlobal: Webhook secret not configured, skipping signature verification');
            return $next($request);
        }

        $signature = $request->header('X-PayGate-Signature');
        $payload = $request->all();

        if (!PayGateGlobal::validateWebhookSignature($payload, $signature)) {
            Log::warning('PayGateGlobal: Invalid webhook signature', [
                'payload' => $payload,
                'signature' => $signature,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}