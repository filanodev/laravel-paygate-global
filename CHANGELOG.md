# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2024-01-08

### Ajouté
- Intégration complète de l'API PayGateGlobal v1 et v2
- Support des paiements FLOOZ et TMONEY
- Initiation de paiements via API (Méthode 1)
- Génération d'URLs de paiement (Méthode 2)
- Vérification du statut des transactions
- Gestion des webhooks sécurisée avec validation de signature
- Système de remboursements (disburse)
- Consultation des soldes
- Événement `PaymentReceived` pour les notifications
- Middleware `VerifyPayGateWebhook` pour la sécurité
- Migration optionnelle pour le stockage des transactions
- Facade `PayGateGlobal` pour un accès facile
- Configuration via fichier .env
- Tests unitaires et fonctionnels complets
- Documentation complète en français
- Support Laravel 8, 9, 10, 11, 12
- Gestion des erreurs et logging avancé

### Fonctionnalités
- ✅ Initiation de paiements API
- ✅ Génération d'URLs de paiement
- ✅ Vérification du statut (par tx_reference ou identifier)
- ✅ Webhooks sécurisés
- ✅ Remboursements automatiques
- ✅ Consultation des soldes
- ✅ Événements Laravel
- ✅ Middleware de sécurité
- ✅ Configuration flexible
- ✅ Messages d'erreur en français
- ✅ Tests automatisés

### Sécurité
- Validation des signatures webhooks avec HMAC SHA256
- Middleware de vérification des webhooks
- Gestion sécurisée des clés API
- Validation des paramètres requis
- Logging des erreurs et tentatives de fraude

### Documentation
- README complet avec exemples
- Configuration détaillée
- Exemples d'utilisation pour tous les cas d'usage
- Guide de migration
- Documentation des événements et listeners