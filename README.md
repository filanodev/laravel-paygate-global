# Laravel PayGateGlobal

Un package Laravel pour l'intégration facile des paiements **PayGateGlobal** avec support pour **FLOOZ** et **TMONEY**.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/filano/laravel-paygate-global.svg?style=flat-square)](https://packagist.org/packages/filano/laravel-paygate-global)
[![License](https://img.shields.io/packagist/l/filano/laravel-paygate-global.svg?style=flat-square)](https://packagist.org/packages/filano/laravel-paygate-global)

## Fonctionnalités

- ✅ **Initiation de paiements** via API ou redirection
- ✅ **Vérification du statut** des transactions
- ✅ **Gestion des webhooks** sécurisée
- ✅ **Remboursements** automatiques
- ✅ **Consultation des soldes**
- ✅ **Support FLOOZ et TMONEY**
- ✅ **Tests complets inclus**
- ✅ **Laravel 8, 9, 10, 11, 12+** compatible

## Installation

```bash
composer require filano/laravel-paygate-global
```

### Publication des fichiers

```bash
# Publier la configuration
php artisan vendor:publish --tag=paygate-global-config

# Publier les migrations (optionnel)
php artisan vendor:publish --tag=paygate-global-migrations

# Exécuter les migrations
php artisan migrate
```

## Configuration

Ajoutez votre clé API PayGateGlobal dans votre fichier `.env`:

```env
# OBLIGATOIRE
PAYGATE_GLOBAL_AUTH_TOKEN=xxxx-xxxxx-468c-81aa-xxxxxxxx

# IMPORTANT - URL où PayGateGlobal enverra les notifications de paiement
# Si non définie, utilisera: https://votre-site.com/paygate-global/webhook
PAYGATE_GLOBAL_CALLBACK_URL=https://votre-site.com/paygate-global/webhook

# OPTIONNEL - Pour la sécurité des webhooks (recommandé)
PAYGATE_GLOBAL_WEBHOOK_SECRET=votre-secret-webhook

# OPTIONNEL - Paramètres avancés (valeurs par défaut)
PAYGATE_GLOBAL_TIMEOUT=30
PAYGATE_GLOBAL_LOG_REQUESTS=true
```

## Utilisation

### 1. Initiation de paiement (API)

```php
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

$response = PayGateGlobal::initiatePayment([
    'phone_number' => '+22890123456',
    'amount' => 1000,
    'identifier' => 'ORDER_' . time(),
    'network' => 'FLOOZ', // ou 'TMONEY'
    'description' => 'Achat produit XYZ'
]);

// Réponse
// {
//     "tx_reference": "TXN123456789",
//     "status": 0  // 0 = succès
// }
```

### 2. Génération d'URL de paiement

```php
$paymentUrl = PayGateGlobal::generatePaymentUrl([
    'amount' => 5000,
    'identifier' => 'ORDER_123',
    'description' => 'Commande #123',
    'success_url' => url('/payment/success'), // URL après paiement réussi
    // ou 'return_url' => url('/payment/callback'), // Alternative
    'phone' => '+22890123456', // Optionnel - pré-remplir
    'network' => 'FLOOZ' // Optionnel - pré-sélectionner
]);

// Rediriger l'utilisateur
return redirect($paymentUrl);
```

### 3. Vérification du statut

```php
// Par référence PayGateGlobal
$status = PayGateGlobal::checkPaymentStatus('TXN123456789');

// Par votre identifiant
$status = PayGateGlobal::checkPaymentStatusByIdentifier('ORDER_123');

// Réponse
// {
//     "tx_reference": "TXN123456789",
//     "status": 0,  // 0=succès, 2=en cours, 4=expiré, 6=annulé
//     "payment_method": "FLOOZ",
//     "amount": 1000
// }
```

### 4. Consultation des soldes

```php
$balance = PayGateGlobal::checkBalance();

// {
//     "flooz": 50000,
//     "tmoney": 25000
// }
```

### 5. Remboursements

```php
$disbursement = PayGateGlobal::disburse([
    'phone_number' => '+22890123456',
    'amount' => 1000,
    'reason' => 'Remboursement commande ORDER_123',
    'network' => 'FLOOZ',
    'reference' => 'REF_' . time() // Optionnel
]);
```

## Gestion des Webhooks

### Configuration du webhook

PayGateGlobal enverra automatiquement les notifications de paiement à votre URL de callback.

**URL de callback par défaut :** `https://votre-site.com/paygate-global/webhook`

```php
// Obtenir l'URL de callback configurée
$callbackUrl = PayGateGlobal::getCallbackUrl();
echo $callbackUrl; // https://votre-site.com/paygate-global/webhook
```

### Écouter les paiements

```php
// Dans un EventServiceProvider
use PayGate\LaravelPayGateGlobal\Events\PaymentReceived;

protected $listen = [
    PaymentReceived::class => [
        YourPaymentReceivedListener::class,
    ],
];
```

### Listener exemple

```php
<?php

namespace App\Listeners;

use PayGate\LaravelPayGateGlobal\Events\PaymentReceived;
use Illuminate\Support\Facades\Log;

class YourPaymentReceivedListener
{
    public function handle(PaymentReceived $event)
    {
        Log::info('Paiement reçu', [
            'tx_reference' => $event->txReference,
            'identifier' => $event->identifier,
            'amount' => $event->amount,
            'phone_number' => $event->phoneNumber,
            'payment_method' => $event->paymentMethod,
        ]);

        // Mettre à jour votre commande
        // Order::where('identifier', $event->identifier)
        //     ->update(['status' => 'paid']);
    }
}
```

## Codes de statut

### Statuts de transaction (initiation)
- `0` : Transaction enregistrée avec succès
- `2` : Jeton d'authentification invalide
- `4` : Paramètres invalides
- `6` : Doublons détectés

### Statuts de paiement
- `0` : Paiement réussi
- `2` : En cours
- `4` : Expiré
- `6` : Annulé

## Migration de données (optionnel)

Le package inclut une migration pour stocker les transactions:

```php
Schema::create('paygate_transactions', function (Blueprint $table) {
    $table->id();
    $table->string('tx_reference')->unique();
    $table->string('identifier');
    $table->decimal('amount', 15, 2);
    $table->string('phone_number');
    $table->enum('network', ['FLOOZ', 'TMONEY']);
    $table->integer('status')->default(2);
    // ... autres colonnes
});
```

## Helper methods

```php
// Messages de statut en français
$message = PayGateGlobal::getStatusMessage(0); // "Paiement réussi avec succès"
$message = PayGateGlobal::getTransactionStatusMessage(2); // "Jeton d'authentification invalide"

// Validation de signature webhook
$isValid = PayGateGlobal::validateWebhookSignature($payload, $signature);
```

## Tests

```bash
composer test
```

## Sécurité

- ✅ Validation des signatures webhooks
- ✅ Middleware de sécurité inclus
- ✅ Gestion des erreurs et logs
- ✅ Validation des paramètres requis

## Configuration avancée

Le fichier `config/paygate-global.php` permet de personnaliser:

- **auth_token** : Votre clé API PayGateGlobal (obligatoire)
- **callback_url** : URL où PayGateGlobal enverra les notifications (important)
- **webhook_secret** : Secret pour valider les webhooks (optionnel, recommandé)
- **timeout** : Délai d'expiration des requêtes HTTP (défaut: 30s)
- **log_requests** : Activer le logging des requêtes (défaut: true)

Les URLs de l'API PayGateGlobal sont hardcodées dans le service (elles ne changent pas).
Les URLs de redirection se passent directement lors de l'appel à `generatePaymentUrl()`.

## Support

- **Laravel** : 8.x, 9.x, 10.x, 11.x, 12.x+
- **PHP** : 8.0+
- **PayGateGlobal** : API v1 et v2

## Licence

MIT License. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Contributeur

- **Filano** - Développeur principal

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) pour l'historique des versions.