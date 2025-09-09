# Laravel PayGateGlobal

Un package Laravel **non-officiel** pour l'intégration facile des paiements **PayGateGlobal** avec support pour **FLOOZ** et **TMONEY**.

> 📝 **Package communautaire** développé par [Filano](https://me.fedapay.com/filano_don) pour aider les développeurs à intégrer plus rapidement PayGateGlobal dans leurs applications Laravel.

**PayGateGlobal** est le premier intégrateur et leader de solutions monétiques au Togo, offrant la façon la plus rapide de recevoir des paiements en ligne via les portefeuilles mobiles africains.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/filano/laravel-paygate-global.svg?style=flat-square)](https://packagist.org/packages/filano/laravel-paygate-global)
[![License](https://img.shields.io/packagist/l/filano/laravel-paygate-global.svg?style=flat-square)](https://packagist.org/packages/filano/laravel-paygate-global)

## 🌍 À propos de PayGateGlobal

[PayGateGlobal](https://paygateglobal.com/) simplifie l'acceptation des paiements en ligne au Togo et en Afrique de l'Ouest :

- **🚀 Intégration rapide** : API simple, intégration en 5 minutes
- **🔒 Sécurisé** : Protection anti-fraude intégrée
- **💳 Paiements mobiles** : FLOOZ (Moov Togo) et T-Money (Togocel)
- **📊 Tableau de bord** : Interface pour voir les paiements et consulter les soldes
- **💰 Tarification transparente** : 2.5% FLOOZ, 3% T-Money, sans frais cachés

## Fonctionnalités

- ✅ **Initiation de paiements** via API ou redirection
- ✅ **Vérification du statut** des transactions
- ✅ **Gestion des webhooks** sécurisée
- ✅ **Remboursements** automatiques
- ✅ **Consultation des soldes**
- ✅ **Support FLOOZ et TMONEY**
- ✅ **Tests complets inclus**
- ✅ **Laravel 8, 9, 10, 11, 12** compatible

## 📋 Prérequis

### Ouverture d'un compte PayGateGlobal

1. **Créer un compte** sur [PayGateGlobal](https://paygateglobal.com/) (gratuit)
2. **Fournir les documents** requis pour l'activation :
   - Carte Formalités des Entreprises ou Carte d'Immatriculation Fiscale
   - Carte d'Identité du Promoteur
   - Descriptif du Projet
   - Contact et lien de retour
3. **Récupérer votre clé API** depuis le tableau de bord après activation

### Paiements et reversements

- **Solde FLOOZ** : Accessible à J+1 via module de remboursement (partenariat MOOV TOGO, ECOBANK, BAT)
- **Solde T-Money** : Reversé tous les 10 jours par PayGate
- **Frais** : 2.5% sur FLOOZ, 3% sur T-Money (aucun frais caché)

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

## 🚀 Comment ça marche ?

### Étape 1 : Créer un compte PayGateGlobal
1. Remplissez le [formulaire d'inscription PayGateGlobal](https://paygateglobal.com/) (gratuit)
2. Recevez l'email de confirmation avec les instructions
3. Accédez à votre tableau de bord (compte inactif par défaut)

### Étape 2 : Activer votre compte  
1. Rendez-vous dans la page de profil de votre compte
2. Fournissez les documents requis :
   - Carte Formalités des Entreprises ou Carte d'Immatriculation Fiscale
   - Carte d'Identité du Promoteur
   - Descriptif du Projet
   - Contact
   - Lien de retour (callback URL)
3. Votre compte est activé après vérification

### Étape 3 : Intégrer en 5 minutes
1. Installez ce package Laravel
2. Configurez votre clé API (disponible dans le tableau de bord)
3. Utilisez les méthodes du package pour accepter les paiements
4. Vos clients peuvent payer immédiatement via FLOOZ ou T-Money

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

# DÉVELOPPEMENT UNIQUEMENT - SSL Certificate
# Désactiver la vérification SSL en environnement local (défaut: true)
PAYGATE_GLOBAL_VERIFY_SSL=false
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
- **verify_ssl** : Vérification SSL pour les appels API (défaut: true, false pour développement local)

### Endpoints API supportés

Le package utilise automatiquement les bons endpoints:
- **API v1** (`https://paygateglobal.com/api/v1`) : Paiements, statut par tx_reference
- **API v2** (`https://paygateglobal.com/api/v2`) : Statut par identifier personnalisé

### Résolution des problèmes SSL

En développement local, si vous rencontrez des erreurs SSL (cURL error 60), ajoutez dans votre `.env`:
```env
PAYGATE_GLOBAL_VERIFY_SSL=false
```
⚠️ **Important** : Ne jamais désactiver SSL en production !

## Support

- **Laravel** : 8.x, 9.x, 10.x, 11.x, 12.x
- **PHP** : 8.0+
- **PayGateGlobal** : API v1 et v2

## 📞 Support PayGateGlobal

### Contact PayGateGlobal
- **Site web** : [https://paygateglobal.com/](https://paygateglobal.com/)
- **Email** : info@paygateglobal.com
- **Téléphone 1** : +228 96 96 21 21
- **Téléphone 2** : +228 92 60 50 32

### Adresse
```
26, Bld de la KARA
Tokoin Forever
Sis à côté de l'ambassade du Niger au 1er Étage
Lomé – TOGO
BP: 30230 Lomé
```

### Intégration et support technique
- **Documentation officielle** : Disponible dans votre tableau de bord PayGateGlobal
- **Guide d'intégration** : Lien rouge "guide d'intégration" après activation du compte
- **Support développeur** : PayGateGlobal peut vous aider à finir l'intégration

## FAQ PayGateGlobal

### Différence entre URL de redirection et Callback URL
- **URL de redirection** (`success_url`) : Ramène le client sur votre site après paiement
- **Callback URL** : Endpoint pour recevoir les confirmations de paiement de PayGateGlobal (webhook)

### Sécurité et fraude
PayGateGlobal protège contre les fraudes grâce à :
- Outils de prévention intégrés
- Équipe de gestion des risques dédiée
- Validation des signatures webhooks

### Devises et moyens de paiement
- **Devise** : FCFA uniquement
- **Moyens de paiement actuels** : FLOOZ et T-Money
- **Évolution** : VISA et MasterCard prévus prochainement

## Licence

MIT License. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👨‍💻 À propos du package

Ce package Laravel a été développé par **Filano** pour aider les développeurs à intégrer plus rapidement PayGateGlobal dans leurs applications Laravel.

### Développeur du package
- **Filano** - Développeur indépendant
- **Objectif** : Simplifier l'intégration PayGateGlobal pour la communauté des développeurs

### 💝 Soutenir le développement

Si ce package vous a fait gagner du temps, vous pouvez soutenir le développement :

**[💰 Faire un don via FedaPay](https://me.fedapay.com/filano_don)**

Votre soutien aide à maintenir et améliorer ce package pour toute la communauté !

---

**Note importante :** Ce package est un projet communautaire indépendant créé pour faciliter l'intégration de PayGateGlobal. Pour toute question relative au service PayGateGlobal lui-même (activation de compte, paiements, etc.), veuillez contacter directement PayGateGlobal.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) pour l'historique des versions.

---

*Package communautaire non-officiel développé par [Filano](https://me.fedapay.com/filano_don) pour faciliter l'intégration de PayGateGlobal dans les applications Laravel.*

*PayGateGlobal © 2016 BHK Konsulting SARL*