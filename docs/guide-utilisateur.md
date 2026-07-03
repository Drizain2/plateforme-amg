# Guide Utilisateur — AMG Plateforme

> Plateforme de gestion pour ateliers de réparation électronique  
> Version : 2026-07

---

## Table des matières

1. [Premiers pas](#1-premiers-pas)
2. [Tableau de bord](#2-tableau-de-bord)
3. [Tickets SAV](#3-tickets-sav)
4. [Gestion des clients](#4-gestion-des-clients)
5. [Facturation](#5-facturation)
6. [Stock et inventaire](#6-stock-et-inventaire)
7. [Achats fournisseurs](#7-achats-fournisseurs)
8. [Rapports](#8-rapports)
9. [Utilisateurs et permissions](#9-utilisateurs-et-permissions)
10. [Paramètres de l'atelier](#10-paramètres-de-latelier)
11. [Abonnement et paiement](#11-abonnement-et-paiement)
12. [Notifications](#12-notifications)
13. [Guide par rôle](#13-guide-par-rôle)
14. [Questions fréquentes](#14-questions-fréquentes)

---

## 1. Premiers pas

### Inscription

1. Accédez à `/register`
2. Renseignez le nom de votre atelier, votre email et un mot de passe
3. Votre atelier est créé et vous êtes connecté en tant qu'**administrateur**

### Connexion

1. Accédez à `/login`
2. Saisissez votre email et mot de passe
3. Vous êtes redirigé vers votre tableau de bord

### Mot de passe oublié

1. Cliquez sur **"Mot de passe oublié"** sur la page de connexion
2. Entrez votre adresse email
3. Suivez le lien reçu par email pour réinitialiser votre mot de passe

### Première configuration recommandée

Avant de commencer à utiliser la plateforme :

1. **Paramètres → Atelier** : renseignez les informations de votre atelier (logo, adresse, téléphone, TVA par défaut)
2. **Stock → Dépôts** : créez votre (vos) dépôt(s) de stockage
3. **Stock → Fournisseurs** : ajoutez vos fournisseurs de pièces
4. **Stock → Catégories** : créez des catégories pour organiser vos articles
5. **Stock → Articles** : renseignez votre catalogue de pièces
6. **Utilisateurs** : invitez vos techniciens et collaborateurs

---

## 2. Tableau de bord

Le tableau de bord (`/dashboard`) affiche une vue synthétique de l'activité de votre atelier :

- **Tickets en cours** : nombre de réparations actives, réparties par statut
- **Stock critique** : articles dont la quantité est en dessous du seuil d'alerte
- **Activité récente** : derniers tickets créés ou modifiés
- **Chiffre d'affaires** : aperçu des encaissements du mois en cours

### Navigation

La barre latérale gauche donne accès à tous les modules. Elle peut être réduite en cliquant sur la flèche (`←`) pour gagner de l'espace.

---

## 3. Tickets SAV

Le module Tickets est le cœur de la plateforme. Chaque ticket représente un appareil confié par un client pour réparation.

### Créer un ticket

1. Cliquez sur **Tickets SAV → Nouveau ticket**
2. Sélectionnez ou créez le **client**
3. Sélectionnez ou créez l'**appareil** (marque, modèle, numéro de série)
4. Décrivez la **panne signalée** par le client
5. Choisissez la **priorité** (Normale, Haute, Urgente)
6. Assignez un **technicien** (optionnel à la création)
7. Indiquez une **date de retour estimée** si connue
8. Cliquez sur **Enregistrer**

Un numéro de référence (ex : `SAV-2026-00042`) est automatiquement généré. Un **token de suivi** est également créé pour que le client puisse suivre l'avancement sans se connecter.

### Statuts d'un ticket

| Statut | Description |
|--------|-------------|
| **Réceptionné** | Appareil pris en charge, diagnostic à réaliser |
| **En diagnostic** | Technicien en cours d'analyse |
| **En attente de pièces** | Pièces commandées, en attente de réception |
| **En réparation** | Réparation en cours |
| **Terminé** | Réparation finalisée, à remettre au client |
| **Rendu** | Appareil remis au client |
| **Annulé** | Ticket annulé (client a repris l'appareil non réparé, etc.) |

### Faire avancer un ticket

1. Ouvrez le ticket
2. Dans la section **Statut**, cliquez sur le bouton correspondant à la prochaine étape
3. Seules les transitions autorisées sont affichées

### Ajouter une note

1. Dans le ticket, section **Notes**
2. Saisissez votre note (observations, appels client, etc.)
3. Cliquez **Ajouter**

Les notes sont horodatées et associées à votre nom.

### Consommer une pièce

1. Dans le ticket, section **Pièces utilisées**
2. Recherchez et sélectionnez la pièce (depuis le stock du dépôt actif)
3. Indiquez la quantité
4. Cliquez **Ajouter**

Le stock est automatiquement décrémenté et un mouvement de stock est enregistré.

### Poser un diagnostic

1. Dans le ticket, cliquez sur **Saisir le diagnostic**
2. Décrivez le problème identifié et les travaux à effectuer
3. Indiquez le prix estimé si souhaité
4. Enregistrez

### Générer une facture

Une fois le ticket en statut **Terminé** ou **Rendu** :
1. Cliquez sur **Créer une facture**
2. La facture est pré-remplie avec les pièces consommées et leurs prix
3. Vous pouvez ajouter des lignes de service (main d'œuvre, frais, etc.)

### Suivi client (lien public)

Chaque ticket possède un lien de suivi unique (`/track/{token}`) que vous pouvez communiquer au client. Ce lien affiche le statut du ticket sans nécessiter de connexion.

---

## 4. Gestion des clients

### Créer un client

1. Menu **Clients → Nouveau client**
2. Renseignez : nom, téléphone, email, adresse
3. Enregistrez

Ou directement depuis la création d'un ticket (champ **Client**).

### Historique client

La fiche d'un client affiche :
- Tous ses appareils enregistrés
- L'historique de ses tickets
- L'historique de ses factures

### Recherche rapide

Utilisez la barre de recherche dans la liste des clients ou lors de la création d'un ticket.

---

## 5. Facturation

### Créer une facture manuellement

1. Menu **Factures → Nouvelle facture**
2. Sélectionnez le client
3. Ajoutez des lignes (services ou pièces)
4. Le total TTC est calculé automatiquement en appliquant le taux de TVA configuré

### Statuts d'une facture

| Statut | Description |
|--------|-------------|
| **Brouillon** | En cours de préparation, modifiable |
| **Envoyée** | Transmise au client |
| **Payée** | Encaissement reçu |
| **Annulée** | Facture annulée |

### Imprimer / Télécharger le PDF

1. Ouvrez la facture
2. Cliquez sur **Télécharger PDF**

Le PDF inclut le logo de votre atelier, les coordonnées, le détail des lignes et les totaux.

### Lien public

Chaque facture possède un lien signé permettant au client de la consulter ou de la télécharger sans se connecter.

---

## 6. Stock et inventaire

### Articles

Un article représente une pièce détachée ou un produit que vous stockez.

**Créer un article** (Stock → Articles → Nouvel article) :
- Nom, référence (SKU)
- Fournisseur, catégorie
- Marques compatibles
- Prix d'achat, prix de vente
- Seuil d'alerte (stock minimum avant déclenchement d'une alerte)

### Dépôts

Un dépôt est un emplacement physique de stockage (magasin principal, réserve, véhicule technique…).

Chaque article peut avoir un stock différent dans chaque dépôt.

**Gérer les utilisateurs d'un dépôt** : un technicien peut être attaché à un ou plusieurs dépôts. Quand il se connecte, il choisit son dépôt de travail si plusieurs lui sont accessibles.

### Mouvements de stock

Les mouvements sont automatiques lors des opérations métier :
- **Entrée** (In) : réception d'une commande fournisseur
- **Sortie** (Out) : consommation sur un ticket
- **Transfert** : déplacement entre dépôts
- **Ajustement** : correction manuelle après comptage physique

Consultez l'historique complet dans **Stock → Mouvements**.

### Alertes de stock

**Stock → Alertes** liste tous les articles dont la quantité est inférieure ou égale au seuil d'alerte configuré. Une notification est également envoyée automatiquement.

### Inventaires

Un inventaire permet de reconcilier le stock théorique avec le stock physique réel.

1. **Stock → Inventaires → Nouvel inventaire**
2. Choisissez le dépôt à inventorier
3. Pour chaque ligne, saisissez la quantité réellement comptée
4. Cliquez **Valider l'inventaire** : les écarts sont enregistrés comme ajustements

---

## 7. Achats fournisseurs

### Créer une commande

1. **Achats → Nouvelle commande**
2. Sélectionnez le fournisseur et le dépôt de réception
3. Ajoutez les articles commandés avec leur quantité et prix unitaire
4. Enregistrez en **Brouillon**

### Réceptionner une commande

1. Ouvrez la commande
2. Cliquez sur **Réceptionner**
3. Le stock de chaque article est automatiquement incrémenté dans le dépôt sélectionné

### Marquer comme payée

Une fois le fournisseur réglé :
1. Ouvrez la commande
2. Cliquez sur **Marquer comme payée**

---

## 8. Rapports

### Rapport de caisse

**Rapports → Rapport de caisse** — affiche les encaissements sur une période sélectionnable :
- Total des factures payées
- Détail par mode de paiement (si configuré)
- Évolution sur la période

---

## 9. Utilisateurs et permissions

### Inviter un utilisateur

1. **Utilisateurs → Inviter un utilisateur**
2. Renseignez : nom, email, rôle
3. Un mot de passe temporaire est généré (visible une seule fois)
4. L'utilisateur peut le modifier dans ses paramètres de profil

### Rôles disponibles

| Rôle | Accès |
|------|-------|
| **Administrateur** | Accès complet à l'atelier (sauf la gestion de la plateforme) |
| **Gestionnaire** | Stock + Tickets + Factures. Pas de gestion des utilisateurs |
| **Technicien** | Tickets SAV + consommation de pièces |
| **Caissière** | Factures + encaissements |

### Personnaliser les permissions

Pour un utilisateur spécifique, il est possible d'accorder ou de retirer des permissions individuelles sans changer son rôle global :

1. **Utilisateurs → [nom de l'utilisateur] → Permissions**
2. Activez ou désactivez les permissions souhaitées
3. Enregistrez

### Activer / Désactiver un utilisateur

1. Dans la liste des utilisateurs, cliquez sur le bouton **Activer/Désactiver**
2. Un utilisateur désactivé ne peut plus se connecter

### Réinitialiser le mot de passe d'un utilisateur

1. Dans la liste, cliquez sur **Réinitialiser le mot de passe**
2. Un nouveau mot de passe temporaire est généré et affiché

---

## 10. Paramètres de l'atelier

Accessible via **Paramètres** dans le menu latéral.

### Onglet Atelier

- **Logo** : image affichée sur les factures et dans l'interface
- **Nom** de l'atelier
- **Email** et **téléphone** de contact
- **Adresse** physique
- **TVA par défaut** : taux appliqué automatiquement aux nouvelles factures

### Onglet Mon profil

- Modifiez votre **nom** et **adresse email**

### Onglet Mot de passe

- Changez votre **mot de passe**
- Nécessite de saisir l'ancien mot de passe

### Onglet Abonnement

Affiche votre plan actuel et ses fonctionnalités. Permet de changer d'offre (redirige vers la page abonnement).

---

## 11. Abonnement et paiement

### Voir votre abonnement

1. Menu **Abonnement** (section Administration dans la barre latérale)
2. La page affiche :
   - Votre plan actuel
   - Le statut de votre abonnement (Actif, En essai, Expiré…)
   - La date d'expiration
   - L'historique de tous vos paiements

### Faire une demande d'abonnement

1. Cliquez sur **Souscrire** ou **Renouveler / Changer de plan**
2. Choisissez la **période** :
   - **Mensuel** : paiement chaque mois
   - **Annuel** : paiement pour 12 mois (≈ 10 mois facturés = 2 mois offerts)
3. Cliquez sur **Envoyer la demande**
4. Une référence de paiement vous est communiquée (ex : `PAY-2026-00001`)
5. **Effectuez le virement** ou le paiement Mobile Money en indiquant cette référence
6. Une notification vous confirme l'activation une fois le paiement validé par l'équipe

### Statuts des paiements

| Statut | Signification |
|--------|--------------|
| **En attente** | Demande reçue, en attente de confirmation de virement |
| **Validé** | Paiement confirmé, abonnement activé |
| **Rejeté** | Paiement non reçu ou problème — contactez le support |
| **Remboursé** | Remboursement effectué |

### Plans disponibles

Consultez la **page publique** `/pricing` pour comparer les offres disponibles et leurs tarifs.

### Accès bloqué (abonnement expiré)

Si votre abonnement expire, vous êtes redirigé vers la page d'abonnement à chaque connexion. Les pages de paramètres, de paiement et de déconnexion restent accessibles.

---

## 12. Notifications

L'icône cloche en haut à droite affiche vos notifications non lues.

### Types de notifications

- **Ticket assigné** : un ticket vous a été assigné
- **Statut modifié** : un ticket que vous suivez a changé de statut
- **Stock critique** : un article que vous gérez est en dessous du seuil d'alerte
- **Facture envoyée** : confirmation d'envoi d'une facture
- **Paiement validé** : votre demande d'abonnement a été approuvée
- **Paiement rejeté** : votre demande d'abonnement n'a pas pu être traitée

### Marquer comme lue

- Cliquez sur une notification pour la marquer comme lue
- Ou cliquez sur **Tout marquer comme lu** pour effacer le compteur

---

## 13. Guide par rôle

### Je suis administrateur de l'atelier

Vous avez accès à toutes les fonctionnalités :
- Configuration de l'atelier et des plans
- Gestion des utilisateurs et de leurs permissions
- Tous les modules (Tickets, Stock, Factures, Achats, Rapports)
- Gestion de l'abonnement et historique des paiements

**Première fois :** configurez l'atelier, créez les dépôts, ajoutez le stock, invitez vos techniciens.

### Je suis technicien

Votre travail se concentre sur les tickets SAV :
1. Connectez-vous et sélectionnez votre dépôt de travail si demandé
2. Consultez vos tickets assignés dans **Tickets SAV**
3. Mettez à jour le statut au fur et à mesure de la réparation
4. Ajoutez des notes pour tracer votre travail
5. Consommez les pièces utilisées pour maintenir le stock à jour

### Je suis gestionnaire de stock

1. Réceptionnez les commandes fournisseurs dans **Achats**
2. Consultez et traitez les alertes de stock dans **Stock → Alertes**
3. Réalisez les inventaires périodiques dans **Stock → Inventaires**
4. Gérez les transferts entre dépôts dans **Stock → Mouvements**

---

## 14. Questions fréquentes

**Comment le client peut-il suivre sa réparation ?**  
Communiquez-lui le lien de suivi du ticket (icône de partage sur la fiche ticket). Ce lien ne nécessite pas de compte.

**Peut-on avoir plusieurs dépôts de stock ?**  
Oui. Créez autant de dépôts que nécessaire dans **Stock → Dépôts**. Chaque article peut avoir un stock différent dans chaque dépôt.

**Que se passe-t-il si je consomme une pièce qui n'est plus en stock ?**  
La plateforme bloque la consommation et affiche un message d'erreur. Vous devez d'abord réceptionner du stock ou effectuer un ajustement.

**Comment annuler une facture payée ?**  
Une facture payée ne peut pas être directement modifiée. Créez un avoir (nouvelle facture en négatif) ou contactez votre administrateur.

**Un technicien peut-il voir les factures ?**  
Par défaut, non. L'administrateur peut activer la permission `invoices.view` pour un technicien spécifique via **Utilisateurs → Permissions**.

**Comment changer la TVA sur une facture déjà créée ?**  
Seules les factures en statut **Brouillon** sont modifiables. Changez le taux de TVA dans les paramètres de la ligne de facture.

**Je ne reçois plus de notifications, que faire ?**  
Vérifiez que les notifications ne sont pas toutes lues (compteur à 0). Si le problème persiste, contactez votre administrateur pour vérifier que le service de file d'attente est actif.

**Mon abonnement a expiré, j'ai payé mais l'accès n'est pas rétabli.**  
Le paiement est validé manuellement. Une fois votre virement reçu et vérifié par notre équipe (généralement sous 24h ouvrables), vous recevrez une notification de confirmation et votre accès sera rétabli.

**Comment exporter mes données ?**  
Actuellement, les exports sont disponibles via le rapport de caisse (PDF). Des exports supplémentaires peuvent être ajoutés sur demande.
