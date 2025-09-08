# Guide de Configuration PayGateGlobal

Ce guide vous accompagne pour configurer correctement PayGateGlobal dans votre application Laravel.

## 🚀 Installation rapide

```bash
composer require filano/laravel-paygate-global
php artisan vendor:publish --tag=paygate-global-config
```

## ⚙️ Configuration étape par étape

### 1. Configuration .env

```env
# OBLIGATOIRE - Votre clé API PayGateGlobal
PAYGATE_GLOBAL_AUTH_TOKEN=xxxx-xxxxx-468c-81aa-xxxxxxxx

# IMPORTANT - URL de callback pour les notifications
PAYGATE_GLOBAL_CALLBACK_URL=https://votre-site.com/paygate-global/webhook

# SÉCURITÉ - Secret pour valider les webhooks (recommandé)
PAYGATE_GLOBAL_WEBHOOK_SECRET=votre-secret-webhook-super-secret

# OPTIONNEL - Paramètres avancés
PAYGATE_GLOBAL_TIMEOUT=30
PAYGATE_GLOBAL_LOG_REQUESTS=true
```

### 2. Configuration dans le dashboard PayGateGlobal

⚠️ **IMPORTANT** : Vous devez configurer l'URL de callback dans votre dashboard PayGateGlobal.

1. **Connectez-vous** à votre dashboard PayGateGlobal
2. **Allez dans les paramètres** de votre compte
3. **URL de callback** : `https://votre-site.com/paygate-global/webhook`
4. **Sauvegardez** la configuration

### 3. Test de la configuration

```php
// Dans un contrôleur ou une route de test
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

public function testConfig()
{
    try {
        // Vérifier la connexion à l'API
        $balance = PayGateGlobal::checkBalance();
        
        // Vérifier l'URL de callback
        $callbackUrl = PayGateGlobal::getCallbackUrl();
        
        return response()->json([
            'status' => 'success',
            'flooz_balance' => $balance['flooz'],
            'tmoney_balance' => $balance['tmoney'],
            'callback_url' => $callbackUrl,
            'message' => 'Configuration OK ✅'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
```

## 🔧 Configuration des événements

### 1. Créer un Listener

```bash
php artisan make:listener PaymentReceivedListener
```

### 2. Enregistrer dans EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
use PayGate\LaravelPayGateGlobal\Events\PaymentReceived;
use App\Listeners\PaymentReceivedListener;

protected $listen = [
    PaymentReceived::class => [
        PaymentReceivedListener::class,
    ],
];
```

### 3. Implémenter le Listener

```php
<?php

namespace App\Listeners;

use PayGate\LaravelPayGateGlobal\Events\PaymentReceived;
use App\Models\Order;
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

        // Logique de votre application
        Order::where('reference', $event->identifier)
            ->update(['status' => 'paid']);
    }
}
```

## 🧪 Test de paiement

### 1. Initier un paiement de test

```php
use PayGate\LaravelPayGateGlobal\Facades\PayGateGlobal;

// Test paiement API
$response = PayGateGlobal::initiatePayment([
    'phone_number' => '+22890123456',
    'amount' => 100, // Montant de test
    'identifier' => 'TEST_' . time(),
    'network' => 'FLOOZ',
    'description' => 'Test de paiement'
]);

// Test URL de paiement
$url = PayGateGlobal::generatePaymentUrl([
    'amount' => 100,
    'identifier' => 'TEST_' . time(),
    'description' => 'Test via URL',
    'success_url' => url('/payment/success')
]);
```

## 📋 Checklist de vérification

- [ ] ✅ Clé API configurée dans `.env`
- [ ] ✅ URL de callback configurée dans `.env`
- [ ] ✅ URL de callback configurée dans le dashboard PayGateGlobal
- [ ] ✅ Secret webhook configuré (optionnel mais recommandé)
- [ ] ✅ Listener créé et enregistré
- [ ] ✅ Test de connexion API réussi
- [ ] ✅ Test de paiement effectué
- [ ] ✅ Webhook reçu et traité

## ❓ Dépannage

### Erreur "auth_token est requis"
```bash
# Vérifiez votre .env
PAYGATE_GLOBAL_AUTH_TOKEN=votre-vraie-clé-api
php artisan config:clear
```

### Webhook non reçu
1. Vérifiez l'URL dans le dashboard PayGateGlobal
2. Vérifiez que votre site est accessible depuis l'extérieur
3. Vérifiez les logs Laravel : `tail -f storage/logs/laravel.log`

### Signature webhook invalide
```bash
# Vérifiez le secret dans .env
PAYGATE_GLOBAL_WEBHOOK_SECRET=le-même-secret-que-dans-paygate
php artisan config:clear
```

## 🆘 Support

Si vous rencontrez des problèmes :

1. **Vérifiez les logs** : `storage/logs/laravel.log`
2. **Testez l'API** avec la fonction de test ci-dessus
3. **Contactez le support** PayGateGlobal pour des questions sur l'API
4. **Créez une issue** sur GitHub pour des bugs du package

## 🔒 Sécurité en production

- ✅ **Utilisez HTTPS** pour toutes les URLs
- ✅ **Configurez le secret webhook** pour valider les notifications
- ✅ **Vérifiez les montants** dans vos listeners
- ✅ **Loggez toutes les transactions** pour audit
- ✅ **Testez en environnement de staging** avant la production