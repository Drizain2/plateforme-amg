# Documentation Technique — AMG Plateforme

> Version : 2026-07  
> Stack : Laravel 13 · Vue 3 · Inertia.js v3 · TailwindCSS v4 · Pest v4  
> Base de données : SQLite (dev) / MySQL ou PostgreSQL (production)

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture générale](#2-architecture-générale)
3. [Dépendances](#3-dépendances)
4. [Base de données](#4-base-de-données)
5. [Multi-tenancy](#5-multi-tenancy)
6. [Modules essentiels](#6-modules-essentiels)
7. [Abonnements et paiements](#7-abonnements-et-paiements)
8. [Permissions et rôles](#8-permissions-et-rôles)
9. [Maintenance](#9-maintenance)
10. [Ajouter un nouveau module](#10-ajouter-un-nouveau-module)
11. [Ajouter une passerelle de paiement](#11-ajouter-une-passerelle-de-paiement)
12. [Déploiement](#12-déploiement)

---

## 1. Vue d'ensemble

AMG Plateforme est un **SaaS multi-tenant** destiné aux ateliers de réparation électronique (téléphones, ordinateurs, électroménager). Chaque atelier souscrit à un plan, gère ses dépôts, son stock, ses techniciens et ses tickets SAV (Service Après-Vente).

**Principe fondamental :** un atelier = un `Shop`. Toutes les données métier appartiennent à un `Shop`. L'isolation est assurée par des portées Eloquent globales, pas par une base de données distincte par tenant.

---

## 2. Architecture générale

```
┌─────────────────────────────────────────────────────────────────┐
│  Navigateur (Vue 3 + Inertia.js)                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │  Layouts     │  │  Pages       │  │  Components/UI       │  │
│  │  AppLayout   │  │  (Inertia    │  │  (Button, Modal,     │  │
│  │  AdminLayout │  │   pages)     │  │   Badge, Input…)     │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└────────────────────────────┬────────────────────────────────────┘
                             │  HTTP + Inertia protocol
┌────────────────────────────▼────────────────────────────────────┐
│  Laravel 13 (backend)                                           │
│                                                                 │
│  Middleware chain:                                              │
│  BootTenantScope → EnsureTenantScope → CheckPermission         │
│  (+ CheckSubscription sur les routes protégées)                 │
│                                                                 │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────────┐  │
│  │  Controllers   │  │   Services     │  │     Models       │  │
│  │  (HTTP layer)  │→ │  (métier)      │→ │  (Eloquent ORM)  │  │
│  └────────────────┘  └────────────────┘  └────────┬─────────┘  │
│                                                    │            │
│  ┌─────────────────────────────────────────────────▼──────────┐ │
│  │  Base de données (SQLite / MySQL / PostgreSQL)             │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Flux d'une requête typique

1. Le navigateur envoie une requête HTTP (avec header `X-Inertia`).
2. `BootTenantScope` charge `current_shop` et `current_depot` dans le conteneur IoC.
3. `EnsureTenantScope` vérifie que l'atelier est actif et redirige au besoin.
4. Le contrôleur récupère les données via les Services ou directement via Eloquent.
5. Les modèles filtrent automatiquement par `shop_id` grâce aux global scopes.
6. Le contrôleur retourne `Inertia::render(...)` → réponse JSON pour Inertia, HTML complet pour la première visite.
7. Vue met à jour l'interface sans rechargement de page.

### Structure des dossiers clés

```
app/
├── Contracts/          Interface PaymentGateway
├── Enums/              Énumérations PHP 8
├── Gateways/           Implémentations des passerelles paiement
│   └── DTOs/           Objets de transfert (PaymentInitResult, WebhookPayload)
├── Http/
│   ├── Controllers/    Contrôleurs HTTP (minces, délèguent aux Services)
│   ├── Middleware/     6 middlewares métier
│   └── Requests/       Form Requests (validation)
├── Models/             Modèles Eloquent + global scopes
├── Notifications/      Notifications base de données
├── Providers/          AppServiceProvider (bindings IoC)
└── Services/           Logique métier isolée et testable

resources/js/
├── actions/            Types TypeScript Wayfinder (auto-générés)
├── Components/         Composants Vue réutilisables
│   └── UI/             Bibliothèque de composants internes
├── Composables/        Hooks Vue (usePermission, useSidebar…)
├── Layouts/            AppLayout, AdminLayout, AuthLayout
├── pages/              Pages Inertia (une page = une route)
├── routes/             Routes nommées TypeScript (auto-générées)
└── types/              Définitions TypeScript (models.d.ts, etc.)
```

---

## 3. Dépendances

### PHP (Composer)

| Paquet | Version | Rôle |
|--------|---------|------|
| `laravel/framework` | ^13.7 | Cœur du framework |
| `inertiajs/inertia-laravel` | ^3.0 | Adaptateur serveur Inertia |
| `laravel/sanctum` | ^4.3 | Authentification par tokens |
| `laravel/wayfinder` | ^0.1 | Génération de routes typées (TypeScript) |
| `spatie/laravel-permission` | ^8.0 | RBAC : rôles et permissions |
| `spatie/laravel-activitylog` | ^4.12 | Journal d'activité / audit |
| `spatie/laravel-medialibrary` | ^11.23 | Gestion de fichiers et médias |
| `spatie/laravel-sluggable` | ^4.0 | Génération automatique de slugs |
| `barryvdh/laravel-dompdf` | ^3.1 | Génération PDF (factures) |
| `symfony/mailer` | ^7.4 | Transport mail |

**Dépendances de développement :** `laravel/pint` (formateur PHP), `pestphp/pest` (tests), `laravel/telescope` (débogage), `laravel/sail` (Docker).

### JavaScript (NPM)

| Paquet | Rôle |
|--------|------|
| `vue` ^3.5 | Framework UI |
| `@inertiajs/vue3` ^3.0 | Client Inertia Vue |
| `tailwindcss` ^4.1 | CSS utilitaire |
| `pinia` ^3.0 | Gestion d'état global |
| `vee-validate` + `zod` | Validation de formulaires |
| `vue-sonner` | Notifications toast |
| `chart.js` + `vue-chartjs` | Graphiques dashboard |
| `dayjs` | Manipulation de dates |
| `@vueuse/core` | Composables utilitaires |
| `@tanstack/vue-table` | Tables de données headless |

### Outils de build

| Outil | Rôle |
|-------|------|
| `vite` ^8.0 | Bundler frontend |
| `laravel-vite-plugin` | Intégration Vite/Laravel |
| `@inertiajs/vite` | SSR Inertia (dev automatique) |
| `typescript` ^5.2 | Typage statique |
| `eslint` ^9 | Linting JS/TS |
| `prettier` ^3 | Formatage frontend |

---

## 4. Base de données

### Schéma conceptuel (41 migrations)

```
plans ──────────────────────────────────────────────────────────────┐
  └── shops ──────────────────────────────────────────────────────┐ │
        ├── users ──────────────────────────────────────────────┐ │ │
        │     └── [pivot] depot_user                            │ │ │
        ├── depots                                              │ │ │
        │     ├── stock_depots ──┐                              │ │ │
        │     └── stock_counts   │                              │ │ │
        ├── suppliers             │                              │ │ │
        ├── categories            │                              │ │ │
        ├── parts ───────────────┘                              │ │ │
        ├── customers                                           │ │ │
        │     └── devices                                       │ │ │
        ├── tickets                                             │ │ │
        │     ├── ticket_events                                 │ │ │
        │     └── ticket_parts                                  │ │ │
        ├── invoices                                            │ │ │
        │     └── invoice_lines                                 │ │ │
        ├── purchases                                           │ │ │
        │     └── purchase_lines                                │ │ │
        ├── stock_movements (liés à tickets/invoices/purchases) │ │ │
        ├── notifications                                       │ │ │
        ├── shop_user_permissions                               │ │ │
        ├── subscriptions ──────────────────────────────────────┘ │ │
        └── payments ───────────────────────────────────────────── ┘ │
                                                                      │
users (super_admin, shop_id=null) ────────────────────────────────────┘
```

### Tables principales

| Table | Description |
|-------|-------------|
| `plans` | Offres d'abonnement (starter, pro, enterprise) |
| `shops` | Ateliers clients (tenants) |
| `users` | Utilisateurs, liés à un shop (ou `null` pour super_admin) |
| `depots` | Dépôts de stock d'un atelier |
| `parts` | Articles en stock |
| `stock_depots` | Quantité d'un article dans un dépôt |
| `stock_movements` | Mouvements de stock (entrée/sortie/transfert) |
| `customers` | Clients de l'atelier |
| `devices` | Appareils des clients |
| `tickets` | Tickets SAV (ordres de réparation) |
| `invoices` | Factures |
| `purchases` | Commandes fournisseurs |
| `subscriptions` | Abonnements actifs ou passés |
| `payments` | Paiements (en attente, validés, rejetés) |
| `notifications` | Notifications en base (Laravel) |
| `shop_user_permissions` | Surcharges de permissions par utilisateur |

### Conventions de nommage

- Clés primaires : `id` (auto-increment)
- Références métier : `SAV-YYYY-NNNNN` (tickets), `FAC-YYYY-NNNNN` (factures), `PAY-YYYY-NNNNN` (paiements)
- Timestamps : `created_at`, `updated_at` sur toutes les tables
- Clé tenant : `shop_id` présente sur toutes les tables métier
- Soft deletes : non utilisés (suppression physique ou désactivation par `is_active`)

---

## 5. Multi-tenancy

### Principe

Le multi-tenancy est implémenté par **global scopes Eloquent** appliqués automatiquement à tous les modèles métier. Il n'y a pas de base de données distincte par tenant.

### Chaîne d'activation

```
Requête HTTP
  ↓
BootTenantScope (middleware global web)
  → charge current_shop depuis la session
  → charge current_depot depuis la session
  → lie ces instances dans le conteneur IoC : app()->instance('current_shop', $shop)

EnsureTenantScope (middleware groupe auth)
  → vérifie que l'utilisateur authentifié appartient au current_shop
  → vérifie que le shop est actif
  → pour les non-admins avec plusieurs dépôts : redirige vers /depot/select
```

### Traits de portée

**`HasShopScope`** (appliqué à : Ticket, Invoice, Part, Customer, Depot, Supplier, Categorie, Purchase, Payment, etc.)
```php
// Filtre automatique SELECT … WHERE shop_id = {current_shop_id}
// Hook creating : force shop_id = current_shop.id sur toute création
```

**`HasDepotScope`** (appliqué à : StockDepot, StockMovement, StockCount)
```php
// Filtre automatique SELECT … WHERE depot_id = {current_depot_id}
```

### Pattern pour les tests cross-tenant

Quand un test doit créer des données dans un **autre** atelier sans que `HasShopScope` ne les réattribue au shop courant :
```php
app()->forgetInstance('current_shop');
$autreShop = Shop::factory()->create();
$donnee = Model::factory()->create(['shop_id' => $autreShop->id]);
app()->instance('current_shop', $this->shop); // restauration
```

---

## 6. Modules essentiels

### 6.1 Tickets SAV

**Flux principal :**
```
Réception → Diagnostic → En attente pièces → En réparation → Terminé → Rendu
                                                                     ↘ Annulé (depuis tout statut)
```

**Fichiers clés :**
- `app/Models/Ticket.php` — état, transitions, scopes de recherche
- `app/Services/TicketService.php` — `transition()`, `addNote()`, `consumePart()`, `assignTechnician()`
- `app/Enums/TicketStatus.php` — `transitions()` définit les changements d'état autorisés
- `app/Http/Controllers/Ticket/TicketController.php`
- `resources/js/pages/Tickets/` — Index, Show, Create

**Logique de consommation de pièce :**
1. Contrôleur appelle `TicketService::consumePart()`
2. Service décrémente `stock_depots.quantity`
3. Crée un `StockMovement` de type `Out` lié au ticket
4. Crée un `TicketPart` enregistrant la pièce consommée et son prix

### 6.2 Gestion de stock

**Entités :**
- `Part` — définition d'un article (SKU, prix, fournisseur, catégorie)
- `StockDepot` — quantité d'un article dans un dépôt donné, avec prix moyen pondéré
- `StockMovement` — journal immuable de tous les mouvements
- `StockCount` — sessions d'inventaire physique

**Service central : `StockService`**
- `in(StockDepot, qty, cost, source)` — entrée de stock, recalcule `avg_cost_price`
- `out(StockDepot, qty, source)` — sortie de stock, lève une exception si stock insuffisant
- `adjust(StockDepot, qty, note)` — ajustement manuel
- `transfer(StockDepot $from, StockDepot $to, qty)` — transfert entre dépôts

**Prix moyen pondéré (PMP) :**
```
nouveau_pmp = (stock_actuel × pmp_actuel + quantité × coût_unitaire) / (stock_actuel + quantité)
```

### 6.3 Facturation

- `InvoiceService::fromTicket()` — crée une facture à partir d'un ticket (reprise automatique des pièces consommées)
- `InvoiceLine` — recalcule `invoice.total_ttc` automatiquement via un observer `saved`/`deleted`
- PDF généré par DomPDF via `InvoiceController::pdf()`
- Lien public signé : `/invoices/{invoice}/pdf/public` (pas d'authentification requise, signé cryptographiquement)

### 6.4 Achats fournisseurs

- `PurchaseService::receive()` — réceptionne une commande et déclenche `StockService::in()` pour chaque ligne
- Statuts : `Draft → Received → Paid` (ou `Cancelled`)

### 6.5 Notifications

Toutes les notifications utilisent le canal `database` (table `notifications` de Laravel). Elles sont envoyées via `$user->notify()` ou `Notification::send($users, ...)`.

| Notification | Déclencheur |
|-------------|------------|
| `TicketAssigned` | Technicien assigné à un ticket |
| `TicketStatusChanged` | Transition de statut |
| `LowStockAlert` | Stock < seuil d'alerte |
| `InvoiceSent` | Facture envoyée au client |
| `PaymentReceived` | Demande de paiement reçue (notifie super_admins) |
| `PaymentValidated` | Paiement approuvé (notifie l'admin de l'atelier) |
| `PaymentRejected` | Paiement rejeté (notifie l'admin de l'atelier) |

---

## 7. Abonnements et paiements

### Architecture extensible

```
interface PaymentGateway
    │
    ├── ManualGateway          ← actif (virement / cash)
    ├── (futur) PayDunyaGateway
    ├── (futur) WaveGateway
    └── (futur) StripeGateway
```

Le gateway actif est lié dans `AppServiceProvider::register()`. **Changer de passerelle = modifier 2 lignes** :
```php
// AppServiceProvider.php
$this->app->bind(PaymentGateway::class, PayDunyaGateway::class);
$this->app->bind('payment.gateway.paydunya', PayDunyaGateway::class);
```

### Cycle de vie d'un paiement

```
Admin atelier → POST /subscription/subscribe
  ↓
SubscriptionService::requestSubscription()
  ├── Plan gratuit → activateFree() → Subscription::active immédiat
  └── Plan payant →
        Payment::create(status=pending)
        ManualGateway::initiate() → instructions de virement
        Notification → super_admins (PaymentReceived)
  ↓
Super admin → POST /admin/payments/{payment}/approve
  ↓
SubscriptionService::validatePayment()
  Payment::update(status=validated)
  createOrExtendSubscription() → extend depuis ends_at si abonnement actif
  Notification → admin atelier (PaymentValidated)
```

### Webhook (pour passerelles automatiques futures)

`POST /webhooks/{gateway}` → `WebhookController::handle()`
1. Résout la gateway par `app('payment.gateway.{name}')`
2. Vérifie la signature HMAC via `$gateway->verifyWebhook($request)`
3. Normalise la payload via `$gateway->parseWebhook($request)` → `WebhookPayload`
4. Délègue à `SubscriptionService::handleWebhook()` (idempotent)

### CheckSubscription middleware

Routes **exemptées** du contrôle d'abonnement :
- `settings.*` — page de paiement
- `subscription.*` — gestion abonnement
- `webhooks.*` — IPN
- `pricing` — page publique
- `admin.*` — administration plateforme
- `login`, `register`, `logout`, `password.*` — authentification
- `depot.select`, `depot.save`, `depot.switch` — navigation interne

---

## 8. Permissions et rôles

### Rôles disponibles

| Rôle | `shop_id` | Accès |
|------|-----------|-------|
| `super_admin` | `null` | Interface `/admin/*` uniquement. Gère plans et paiements. |
| `admin` | id de l'atelier | Toutes les fonctionnalités de son atelier |
| `manager` | id de l'atelier | Stock + Tickets + Factures (sans gestion utilisateurs) |
| `technicien` | id de l'atelier | Tickets assignés + consommation de pièces |
| `caissiere` | id de l'atelier | Factures + Encaissements |

### Système de permissions

1. **Permissions par défaut par rôle** : définies dans `RolesPermissionsSeeder` (ex: `tickets.view`, `stock.edit`)
2. **Surcharges par utilisateur** : table `shop_user_permissions` permet d'activer ou désactiver une permission spécifique pour un utilisateur sans changer son rôle
3. **Résolution** : `PermissionService::has($user, $shop, $permission)` → vérifie d'abord les surcharges, puis les permissions du rôle
4. **Middleware** : `perm:tickets.edit` → appelle `CheckPermission` → délègue à `PermissionService`

### Plans et modules désactivés

Un plan peut désactiver des modules entiers via `disabled_modules` (liste blanche : `['tickets']`). Cela bloque les permissions du module concerné même pour les utilisateurs qui les auraient normalement.

---

## 9. Maintenance

### Commandes artisan utiles

```bash
# Vérifier les routes
php artisan route:list --except-vendor

# Lancer les tests
php artisan test --compact

# Formater le code PHP
vendor/bin/pint --dirty

# Régénérer les types TypeScript Wayfinder
php artisan wayfinder:generate

# Vider les caches
php artisan optimize:clear

# Voir la configuration active
php artisan config:show database
php artisan config:show app
```

### Cycle de développement

```bash
# 1. Démarrer l'environnement
composer run dev          # Lance PHP + Vite en parallèle

# 2. Après chaque modification PHP
vendor/bin/pint --dirty   # Formatage obligatoire avant commit

# 3. Après ajout/modification de routes ou contrôleurs
php artisan wayfinder:generate   # Regénère les types TypeScript

# 4. Avant tout commit
php artisan test --compact       # 302 tests, ~3 min

# 5. Via Docker
docker-compose up -d      # Lance MySQL + Adminer
```

### Ajouter une migration

```bash
php artisan make:migration add_xxx_to_yyy_table
# Éditer la migration
php artisan migrate

# Si modèle à créer également
php artisan make:model MonModel -mfs   # -m migration, -f factory, -s seeder
```

### Logs et débogage

- **Telescope** : disponible sur `/telescope` (dev uniquement)
- **Logs Laravel** : `storage/logs/laravel.log`
- **Pail** : `php artisan pail` — tail des logs en temps réel
- **Browser logs** (MCP Boost) : `browser-logs` pour inspecter les erreurs console

### Sauvegardes

Points critiques à sauvegarder :
1. Base de données (export SQL)
2. `storage/app/public/` (logos, médias uploadés)
3. `.env` (variables d'environnement)

---

## 10. Ajouter un nouveau module

Exemple : ajouter un module **Garanties** (warranties).

### Étape 1 — Modèle et migration

```bash
php artisan make:model Warranty -mf
```

Ajouter dans la migration :
```php
$table->foreignId('shop_id')->constrained()->cascadeOnDelete();
$table->foreignId('ticket_id')->constrained();
$table->date('starts_at');
$table->date('ends_at');
$table->string('coverage');
```

Ajouter dans le modèle les traits de portée multi-tenant :
```php
use HasShopScope; // filtre automatique par shop_id
```

### Étape 2 — Service (logique métier)

```bash
php artisan make:class Services/WarrantyService
```

Isoler toute la logique métier dans le service. Le contrôleur ne fait qu'appeler le service.

### Étape 3 — Contrôleur

```bash
php artisan make:controller WarrantyController
```

Conventions :
- Contrôleur mince : valide la requête → appelle le service → retourne `Inertia::render()`
- Utiliser `Form Requests` pour la validation : `php artisan make:request StoreWarrantyRequest`

### Étape 4 — Routes

Dans `routes/web.php`, à l'intérieur du groupe `auth, EnsureTenantScope` :
```php
Route::resource('warranties', WarrantyController::class)->except('create', 'edit');
```

Puis régénérer les types :
```bash
php artisan wayfinder:generate
```

### Étape 5 — Vue Inertia

```
resources/js/pages/Warranties/
├── Index.vue
└── Show.vue
```

Importer `WarrantyController` depuis `@/actions/...` (généré par Wayfinder).

### Étape 6 — Sidebar

Dans `resources/js/Components/UI/Sidebar.vue`, ajouter l'entrée dans le groupe concerné.

### Étape 7 — Permissions

Ajouter les permissions dans `RolesPermissionsSeeder` (ex: `warranties.view`, `warranties.edit`) et affecter les rôles concernés.

### Étape 8 — Tests

```bash
php artisan make:test --pest WarrantyTest
```

Pattern standard pour les tests de contrôleur :
```php
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->shop = Shop::factory()->create();
    $this->admin = User::factory()->admin()->create(['shop_id' => $this->shop->id]);
    app()->instance('current_shop', $this->shop);
});
```

---

## 11. Ajouter une passerelle de paiement

Exemple : intégrer **PayDunya**.

### Étape 1 — Créer la classe gateway

```bash
php artisan make:class Gateways/PayDunyaGateway
```

```php
// app/Gateways/PayDunyaGateway.php
class PayDunyaGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paydunya';
    }

    public function initiate(Shop $shop, Plan $plan, BillingPeriod $period, Payment $payment): PaymentInitResult
    {
        // Appel API PayDunya pour créer une session de paiement
        $response = Http::post('https://app.paydunya.com/api/v1/checkout-invoice/create', [
            'invoice' => ['total_amount' => $payment->amount, 'description' => "Abonnement {$plan->name}"],
            'store'   => ['name' => config('app.name')],
            'actions' => ['callback_url' => route('webhooks.handle', 'paydunya')],
        ]);

        return new PaymentInitResult(
            reference:        $payment->reference,
            redirectUrl:      $response->json('response_text'), // URL de paiement PayDunya
            gatewayPaymentId: $response->json('token'),
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        // Vérifier le hash HMAC envoyé par PayDunya
        $expectedHash = hash_hmac('sha512', $request->getContent(), config('services.paydunya.private_key'));
        return hash_equals($expectedHash, $request->header('X-PayDunya-Signature') ?? '');
    }

    public function parseWebhook(Request $request): WebhookPayload
    {
        $data = $request->json()->all();
        $status = $data['status'] === 'completed' ? PaymentStatus::Validated : PaymentStatus::Rejected;

        return new WebhookPayload(
            reference:        $data['custom_data']['reference'],
            status:           $status,
            gatewayPaymentId: $data['token'],
            rawPayload:       $data,
        );
    }
}
```

### Étape 2 — Enregistrer le binding

Dans `AppServiceProvider::register()` :
```php
// Changer la gateway active
$this->app->bind(PaymentGateway::class, PayDunyaGateway::class);

// Ajouter le binding nommé pour le WebhookController
$this->app->bind('payment.gateway.paydunya', PayDunyaGateway::class);
```

### Étape 3 — Configuration

Dans `config/services.php` :
```php
'paydunya' => [
    'private_key' => env('PAYDUNYA_PRIVATE_KEY'),
    'master_key'  => env('PAYDUNYA_MASTER_KEY'),
],
```

### Étape 4 — Frontend (optionnel)

Si la gateway génère une URL de redirection, le contrôleur `SubscriptionController::subscribe()` retourne déjà `$result->redirectUrl`. Il suffit d'utiliser cette URL côté Vue pour rediriger l'utilisateur.

### Ce qui ne change pas

- `SubscriptionService` — aucune modification
- `WebhookController` — aucune modification
- `routes/web.php` — `/webhooks/{gateway}` gère déjà PayDunya
- Tous les tests existants — aucun impact

---

## 12. Déploiement

### Variables d'environnement requises

```env
APP_NAME="AMG Plateforme"
APP_ENV=production
APP_KEY=          # php artisan key:generate
APP_URL=https://votre-domaine.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amg_plateforme
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=database   # ou redis pour la production
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
```

### Checklist de déploiement

```bash
# 1. Installer les dépendances
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Configurer
php artisan key:generate
php artisan migrate --force

# 3. Seeder initial (première installation uniquement)
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PlanSeeder
php artisan db:seed --class=PlatformAdminSeeder

# 4. Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Permissions fichiers (Linux)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Workers queue (notifications asynchrones)

Les notifications implémentent `ShouldQueue`. En production, démarrer un worker :
```bash
php artisan queue:work --sleep=3 --tries=3
```

Ou utiliser `php artisan queue:listen` en développement.

### Via Docker (dev local)

```bash
docker-compose up -d   # Lance MySQL + Adminer (port 8080)
composer run dev       # Laravel + Vite
```
