# Exemples d'utilisation - Laravel PayGateGlobal

Ce fichier contient des exemples pratiques d'utilisation du package Laravel PayGateGlobal.

## Installation rapide

```bash
composer require filano/laravel-paygate-global
php artisan vendor:publish --tag=paygate-global-config
```

## Configuration .env

```env
# Configuration minimale
PAYGATE_GLOBAL_AUTH_TOKEN=xxxx-xxxxx-468c-81aa-xxxxxxxx

# URL de callback pour les notifications de paiement (important)
PAYGATE_GLOBAL_CALLBACK_URL=https://votre-site.com/paygate-global/webhook

# Sécurité (recommandé)
PAYGATE_GLOBAL_WEBHOOK_SECRET=votre-secret-webhook

# Optionnel (valeurs par défaut)
PAYGATE_GLOBAL_TIMEOUT=30
PAYGATE_GLOBAL_LOG_REQUESTS=true
```

## Exemples d'utilisation

### 1. Contrôleur de paiement simple

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:100',
            'network' => 'required|in:FLOOZ,TMONEY'
        ]);

        try {
            $response = PayGateGlobal::initiatePayment([
                'phone_number' => $request->phone,
                'amount' => $request->amount,
                'identifier' => 'ORDER_' . time() . '_' . auth()->id(),
                'network' => $request->network,
                'description' => 'Paiement commande #' . time()
            ]);

            if ($response['status'] === 0) {
                return response()->json([
                    'success' => true,
                    'tx_reference' => $response['tx_reference'],
                    'message' => 'Paiement initié avec succès'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => PayGateGlobal::getTransactionStatusMessage($response['status'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement'
            ], 500);
        }
    }

    public function redirect(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $identifier = 'ORDER_' . time() . '_' . auth()->id();
        
        $paymentUrl = PayGateGlobal::generatePaymentUrl([
            'amount' => $request->amount,
            'identifier' => $identifier,
            'description' => 'Commande #' . $identifier,
            'success_url' => route('payment.callback'), // URL de retour après paiement
            'phone' => $request->phone ?? '', // Pré-remplir le numéro (optionnel)
            'network' => $request->network ?? '' // Pré-sélectionner le réseau (optionnel)
        ]);

        return redirect($paymentUrl);
    }

    public function callback(Request $request)
    {
        $identifier = $request->get('identifier');
        
        if (!$identifier) {
            return redirect('/')->with('error', 'Identifiant de transaction manquant');
        }

        $status = PayGateGlobal::checkPaymentStatusByIdentifier($identifier);

        if ($status['status'] === 0) {
            return redirect('/')->with('success', 'Paiement effectué avec succès!');
        }

        return redirect('/')->with('error', 'Paiement non confirmé');
    }

    public function status($txReference)
    {
        try {
            $status = PayGateGlobal::checkPaymentStatus($txReference);
            
            return response()->json([
                'tx_reference' => $status['tx_reference'],
                'status' => $status['status'],
                'message' => PayGateGlobal::getStatusMessage($status['status']),
                'payment_method' => $status['payment_method'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Transaction non trouvée'], 404);
        }
    }
}
```

### 2. Gestion des commandes avec événements

```php
<?php

namespace App\Listeners;

use PayGate\LaravelPayGateGlobal\Events\PaymentReceived;
use App\Models\Order;
use App\Mail\PaymentConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PaymentReceivedListener
{
    public function handle(PaymentReceived $event)
    {
        Log::info('Paiement reçu', [
            'tx_reference' => $event->txReference,
            'identifier' => $event->identifier,
            'amount' => $event->amount,
        ]);

        // Trouver la commande par identifiant
        $order = Order::where('reference', $event->identifier)->first();
        
        if (!$order) {
            Log::error('Commande non trouvée', ['identifier' => $event->identifier]);
            return;
        }

        // Vérifier le montant
        if ($order->total != $event->amount) {
            Log::error('Montant incorrect', [
                'expected' => $order->total,
                'received' => $event->amount,
                'identifier' => $event->identifier
            ]);
            return;
        }

        // Mettre à jour la commande
        $order->update([
            'status' => 'paid',
            'payment_reference' => $event->paymentReference,
            'payment_method' => $event->paymentMethod,
            'payment_phone' => $event->phoneNumber,
            'payment_date' => now(),
            'paygate_tx_reference' => $event->txReference
        ]);

        // Envoyer email de confirmation
        if ($order->user && $order->user->email) {
            Mail::to($order->user->email)->send(new PaymentConfirmation($order));
        }

        Log::info('Commande mise à jour après paiement', [
            'order_id' => $order->id,
            'status' => 'paid'
        ]);
    }
}
```

### 3. Système de remboursement

```php
<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Order;
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

class RefundController extends Controller
{
    public function refund(Request $request, Order $order)
    {
        if ($order->status !== 'paid') {
            return back()->with('error', 'Cette commande ne peut pas être remboursée');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'amount' => 'numeric|min:100|max:' . $order->total
        ]);

        $refundAmount = $request->amount ?? $order->total;

        try {
            $response = PayGateGlobal::disburse([
                'phone_number' => $order->payment_phone,
                'amount' => $refundAmount,
                'reason' => $request->reason,
                'network' => $order->payment_method,
                'reference' => 'REFUND_' . $order->reference
            ]);

            if ($response['status'] === 200) {
                $order->update([
                    'status' => 'refunded',
                    'refund_reference' => $response['tx_reference'],
                    'refund_amount' => $refundAmount,
                    'refund_date' => now(),
                    'refund_reason' => $request->reason
                ]);

                return back()->with('success', 'Remboursement effectué avec succès');
            }

            return back()->with('error', 'Erreur lors du remboursement');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du remboursement: ' . $e->getMessage());
        }
    }
}
```

### 4. Dashboard avec consultation des soldes

```php
<?php

namespace App\Http\Controllers\Admin;

use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $balance = PayGateGlobal::checkBalance();
            $callbackUrl = PayGateGlobal::getCallbackUrl();
            
            return view('admin.dashboard', [
                'flooz_balance' => $balance['flooz'] ?? 0,
                'tmoney_balance' => $balance['tmoney'] ?? 0,
                'total_balance' => ($balance['flooz'] ?? 0) + ($balance['tmoney'] ?? 0),
                'callback_url' => $callbackUrl // URL pour configurer dans PayGateGlobal
            ]);
        } catch (\Exception $e) {
            return view('admin.dashboard', [
                'flooz_balance' => 'Erreur',
                'tmoney_balance' => 'Erreur',
                'total_balance' => 'Erreur'
            ]);
        }
    }
}
```

### 5. Commande Artisan pour vérifier les paiements

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

class CheckPendingPayments extends Command
{
    protected $signature = 'payments:check-pending';
    protected $description = 'Vérifier le statut des paiements en attente';

    public function handle()
    {
        $pendingOrders = Order::where('status', 'pending')
            ->whereNotNull('paygate_tx_reference')
            ->get();

        $this->info("Vérification de {$pendingOrders->count()} paiements en attente...");

        foreach ($pendingOrders as $order) {
            try {
                $status = PayGateGlobal::checkPaymentStatus($order->paygate_tx_reference);
                
                if ($status['status'] === 0) {
                    $order->update(['status' => 'paid']);
                    $this->info("✅ Commande {$order->reference} marquée comme payée");
                } elseif (in_array($status['status'], [4, 6])) {
                    $order->update(['status' => 'failed']);
                    $this->warn("❌ Commande {$order->reference} marquée comme échouée");
                }
                
            } catch (\Exception $e) {
                $this->error("Erreur pour la commande {$order->reference}: " . $e->getMessage());
            }
        }

        $this->info('Vérification terminée.');
    }
}
```

### 6. Blade Component pour le formulaire de paiement

```php
<!-- resources/views/components/payment-form.blade.php -->
<div class="payment-form">
    <form action="{{ route('payment.initiate') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="network">Méthode de paiement</label>
            <select name="network" id="network" class="form-control" required>
                <option value="">Choisir...</option>
                <option value="FLOOZ">FLOOZ (Moov)</option>
                <option value="TMONEY">TMONEY (Togocel)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="phone">Numéro de téléphone</label>
            <input type="tel" name="phone" id="phone" class="form-control" 
                   placeholder="+22890123456" required>
        </div>

        <div class="form-group">
            <label for="amount">Montant (FCFA)</label>
            <input type="number" name="amount" id="amount" class="form-control" 
                   min="100" value="{{ $amount ?? '' }}" required>
        </div>

        <button type="submit" class="btn btn-primary">
            Payer maintenant
        </button>
    </form>

    <!-- Ou utiliser la méthode de redirection -->
    <div class="text-center mt-3">
        <p>Ou</p>
        <a href="{{ route('payment.redirect') }}?amount={{ $amount ?? 1000 }}" 
           class="btn btn-secondary">
            Payer via la page PayGateGlobal
        </a>
    </div>
</div>
```

### 7. JavaScript pour vérification de statut en temps réel

```javascript
// public/js/payment-status.js
class PaymentStatus {
    constructor(txReference) {
        this.txReference = txReference;
        this.checkInterval = null;
        this.maxChecks = 60; // 5 minutes maximum
        this.currentCheck = 0;
    }

    startChecking() {
        this.checkInterval = setInterval(() => {
            this.checkPaymentStatus();
        }, 5000); // Vérifier toutes les 5 secondes
    }

    async checkPaymentStatus() {
        try {
            const response = await fetch(`/api/payment/status/${this.txReference}`);
            const data = await response.json();

            this.updateUI(data);

            if (data.status === 0) {
                this.onPaymentSuccess(data);
                this.stopChecking();
            } else if ([4, 6].includes(data.status)) {
                this.onPaymentFailed(data);
                this.stopChecking();
            }

            this.currentCheck++;
            if (this.currentCheck >= this.maxChecks) {
                this.onTimeout();
                this.stopChecking();
            }
        } catch (error) {
            console.error('Erreur lors de la vérification:', error);
        }
    }

    updateUI(data) {
        const statusElement = document.getElementById('payment-status');
        if (statusElement) {
            statusElement.textContent = data.message;
            statusElement.className = `status-${data.status}`;
        }
    }

    onPaymentSuccess(data) {
        alert('Paiement effectué avec succès!');
        window.location.href = '/payment/success';
    }

    onPaymentFailed(data) {
        alert('Paiement échoué: ' + data.message);
        window.location.href = '/payment/failed';
    }

    onTimeout() {
        alert('Vérification du paiement expirée. Veuillez contacter le support.');
    }

    stopChecking() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }
}

// Utilisation
document.addEventListener('DOMContentLoaded', function() {
    const txReference = document.querySelector('[data-tx-reference]')?.dataset.txReference;
    if (txReference) {
        const checker = new PaymentStatus(txReference);
        checker.startChecking();
    }
});
```

## Routes recommandées

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::post('/payment/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
    Route::get('/payment/redirect', [PaymentController::class, 'redirect'])->name('payment.redirect');
    Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');
});

// routes/api.php
Route::get('/payment/status/{txReference}', [PaymentController::class, 'status']);
```

Ces exemples montrent comment intégrer facilement PayGateGlobal dans une application Laravel complète avec gestion des commandes, remboursements, et vérification des statuts en temps réel.