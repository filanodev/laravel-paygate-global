<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayGateGlobal Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration simplifiée pour l'intégration PayGateGlobal
    | Support des paiements FLOOZ et TMONEY
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Auth Token (Clé API) - OBLIGATOIRE
    |--------------------------------------------------------------------------
    |
    | Votre jeton d'authentification fourni par PayGateGlobal
    | Exemple: xxxx-xxxxx-468c-81aa-xxxxxxxx
    |
    */
    'auth_token' => env('PAYGATE_GLOBAL_AUTH_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret - OPTIONNEL
    |--------------------------------------------------------------------------
    |
    | Secret pour valider les webhooks (recommandé pour la sécurité)
    | Si non défini, les webhooks ne seront pas validés
    |
    */
    'webhook_secret' => env('PAYGATE_GLOBAL_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Timeout - OPTIONNEL
    |--------------------------------------------------------------------------
    |
    | Timeout pour les requêtes HTTP en secondes (défaut: 30)
    |
    */
    'timeout' => env('PAYGATE_GLOBAL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Logging - OPTIONNEL
    |--------------------------------------------------------------------------
    |
    | Activer/désactiver le logging des requêtes (défaut: true)
    |
    */
    'log_requests' => env('PAYGATE_GLOBAL_LOG_REQUESTS', true),

    /*
    |--------------------------------------------------------------------------
    | Callback URL - IMPORTANT
    |--------------------------------------------------------------------------
    |
    | URL où PayGateGlobal enverra les notifications de paiement
    | Si non définie, utilisera l'URL par défaut du webhook du package
    | Format: https://votre-site.com/paygate-global/webhook
    |
    */
    'callback_url' => env('PAYGATE_GLOBAL_CALLBACK_URL'),
];