# Roadmap SaaS — Plateforme AMG

> Objectif : transformer l'application en un SaaS complet et autonome.
> Les items sont classés par priorité d'implémentation.

---

## Priorité 1 — Bloquant pour la mise en production commerciale

### ✅ Fait
- [x] Multi-tenancy avec isolation des données (HasShopScope / HasDepotScope)
- [x] Plans d'abonnement (Starter / Pro / Enterprise)
- [x] Cycle de paiement manuel avec validation super_admin
- [x] Middleware CheckSubscription (blocage si abonnement expiré)
- [x] Rôles et permissions granulaires
- [x] Modules métier complets (tickets, stock, factures, achats)
- [x] Notifications en base de données
- [x] Architecture PaymentGateway extensible

### ✅ 1. Email transactionnel + vérification email
**Pourquoi :** sans email, aucune confiance ni engagement utilisateur possible.
- [ ] `MustVerifyEmail` sur le modèle `User`
- [ ] Template email de vérification d'adresse
- [ ] Email de bienvenue après inscription
- [ ] Emails de notification : paiement reçu / validé / rejeté
- [ ] Email de rappel d'expiration d'abonnement (J-7, J-1)
- [ ] Configurer un mailer (Resend, Mailgun ou SMTP)

### ✅ 2. Passerelle de paiement réelle — PayDunya / Wave
**Pourquoi :** le paiement manuel ne passe pas à l'échelle.
- [ ] Implémenter `PayDunyaGateway` (l'interface est prête)
- [ ] Implémenter `WaveGateway`
- [ ] Sélection de gateway dans les settings admin
- [ ] Tests d'intégration webhook PayDunya / Wave

### ✅ 3. Renouvellement automatique + dunning
**Pourquoi :** sans renouvellement automatique, chaque paiement est manuel.
- [ ] Commande planifiée : détecter les abonnements expirant dans 7 jours
- [ ] Tenter le renouvellement automatique via gateway active
- [ ] Dunning : 3 tentatives espacées (J-1, J+3, J+7) avant suspension
- [ ] Emails de relance automatiques
- [ ] Suspension automatique après échec du dunning

### ✅ 4. Période d'essai automatique
**Pourquoi :** `trial_ends_at` existe mais rien ne la gère.
- [ ] Commande planifiée quotidienne : passer les essais expirés en `Expired`
- [ ] Email à J-3 avant fin d'essai
- [ ] Banner "votre essai se termine dans X jours" sur le dashboard
- [ ] Flow de conversion essai → payant dans l'UI

### ✅ 5. Dashboard super_admin avec KPIs SaaS
**Pourquoi :** visibilité indispensable sur la santé du business.
- [ ] MRR (Monthly Recurring Revenue) et ARR
- [ ] Ateliers actifs / en essai / churned / suspendus
- [ ] Churn rate mensuel
- [ ] Taux de conversion essai → payant
- [ ] Graphe d'acquisition (nouveaux ateliers / semaine)
- [ ] Paiements en attente mis en avant
- [ ] Impersonation : se connecter en tant qu'atelier pour le support

---

## Priorité 2 — Professionnalisation du SaaS

### ✅ 6. Sécurité renforcée
- [ ] 2FA (TOTP — Google Authenticator / Authy) via Laravel Fortify
- [ ] Gestion des sessions actives (liste + révocation)
- [ ] Journal d'audit visible par atelier (UI sur `spatie/laravel-activitylog`)
- [ ] Rate limiting sur login, inscription, reset password

### 🔲 7. Onboarding guidé
- [ ] Wizard de première configuration (atelier → dépôt → article → ticket)
- [ ] Checklist de progression sur le dashboard ("3/6 étapes complétées")
- [ ] Données de démonstration chargeables et supprimables par l'atelier

### 🔲 8. Suspension et réactivation d'atelier
- [ ] UI super_admin : bouton suspendre / réactiver en un clic
- [ ] Email de notification à l'atelier lors de suspension / réactivation
- [ ] Motif de suspension (CGU, non-paiement, fraude)

### 🔲 9. API publique REST
- [ ] Authentification par API token Sanctum
- [ ] Gestion des API keys par atelier (génération, révocation, scopes)
- [ ] Endpoints : tickets, clients, stock, factures
- [ ] Documentation OpenAPI auto-générée (`dedoc/scramble`)
- [ ] Webhooks sortants (ticket mis à jour → endpoint externe)

---

## Priorité 3 — Rétention et différenciation

### 🔲 10. Notifications SMS / WhatsApp
- [ ] Canal SMS via agrégateur (Orange, Twilio)
- [ ] Canal WhatsApp (API Business ou via passerelle locale)
- [ ] SMS au client quand son appareil est prêt
- [ ] Alerte SMS au gérant pour stock critique

### 🔲 11. Portail client (sans compte)
- [ ] Accès par email + OTP (pas de mot de passe)
- [ ] Vue de tous les appareils et tickets du client
- [ ] Téléchargement de factures
- [ ] Approbation de devis avant réparation

### 🔲 12. Rapports et exports avancés
- [ ] Export CSV / Excel de toutes les listes
- [ ] Rapport de performance technicien
- [ ] Analyse des pannes les plus fréquentes
- [ ] Rapport fournisseur (commandes, délais, montants)

### 🔲 13. Multi-langue (i18n)
- [ ] Internationalisation backend (`lang/` + `__()`)
- [ ] Internationalisation frontend (`vue-i18n`)
- [ ] Français (défaut) + Anglais

### 🔲 14. PWA (Progressive Web App)
- [ ] Manifest + Service Worker
- [ ] Installation sur téléphone depuis le navigateur
- [ ] Fonctionnement hors-ligne partiel (consultation tickets en cache)

---

## Priorité 4 — Infrastructure à l'échelle

### ✅ 15. Sauvegardes automatisées
- [ ] `spatie/laravel-backup` : BDD + fichiers uploadés
- [ ] Stockage distant (S3, Google Drive ou FTP)
- [ ] Notification si la sauvegarde échoue

### 🔲 16. Monitoring applicatif
- [ ] Sentry : capture automatique des erreurs PHP + JS
- [ ] Uptime monitoring (UptimeRobot, Better Stack)
- [ ] Health endpoint `/up` branché sur le monitoring

### 🔲 17. Redis + Laravel Horizon
- [ ] Remplacer la queue `database` par Redis
- [ ] Horizon pour la visibilité sur les jobs (échecs, temps de traitement)
- [ ] Métriques de queue dans le dashboard super_admin

### 🔲 18. RGPD et conformité
- [ ] Export des données d'un atelier (droit à la portabilité)
- [ ] Workflow de suppression de compte (cascade complète)
- [ ] Politique de rétention (purge après X années d'inactivité)
- [ ] Log horodaté du consentement aux CGU à l'inscription

---

## Ordre recommandé

```
1 → 4 → 2 → 3 → 5 → 6 → 15 → 16 → 7 → 8 → 9 → 17 → 10 → 11 → 12 → 18 → 13 → 14
```

Les items 15 et 16 (sauvegardes + monitoring) doivent être en place avant toute mise en production réelle.
