<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayGateGlobal Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration PayGateGlobal
    | Supports des paiements FLOOZ et TMONEY
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Auth Token (Clé API)
    |--------------------------------------------------------------------------
    |
    | Votre jeton d'authentification fourni par PayGateGlobal
    |
    */
    'auth_token' => env('PAYGATE_GLOBAL_AUTH_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | L'URL de base pour l'API PayGateGlobal
    |
    */
    'base_url' => env('PAYGATE_GLOBAL_BASE_URL', 'https://paygateglobal.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Payment Page URL
    |--------------------------------------------------------------------------
    |
    | L'URL pour la page de paiement PayGateGlobal (Méthode 2)
    |
    */
    'payment_page_url' => env('PAYGATE_GLOBAL_PAYMENT_URL', 'https://paygateglobal.com/v1/page'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secret pour valider les webhooks (optionnel)
    |
    */
    'webhook_secret' => env('PAYGATE_GLOBAL_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout pour les requêtes HTTP en secondes
    |
    */
    'timeout' => env('PAYGATE_GLOBAL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Activer/désactiver le logging des requêtes
    |
    */
    'log_requests' => env('PAYGATE_GLOBAL_LOG_REQUESTS', true),

    /*
    |--------------------------------------------------------------------------
    | Networks
    |--------------------------------------------------------------------------
    |
    | Réseaux supportés
    |
    */
    'networks' => [
        'FLOOZ' => 'FLOOZ',
        'TMONEY' => 'TMONEY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Route
    |--------------------------------------------------------------------------
    |
    | Route pour recevoir les notifications de paiement
    |
    */
    'webhook_route' => env('PAYGATE_GLOBAL_WEBHOOK_ROUTE', 'paygate-global/webhook'),

    /*
    |--------------------------------------------------------------------------
    | Success URL
    |--------------------------------------------------------------------------
    |
    | URL de redirection après un paiement réussi
    |
    */
    'success_url' => env('PAYGATE_GLOBAL_SUCCESS_URL', '/payment/success'),

    /*
    |--------------------------------------------------------------------------
    | Cancel URL
    |--------------------------------------------------------------------------
    |
    | URL de redirection après annulation du paiement
    |
    */
    'cancel_url' => env('PAYGATE_GLOBAL_CANCEL_URL', '/payment/cancel'),
];