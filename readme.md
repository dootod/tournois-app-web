# Tournois App

Application complète de gestion d'un club sportif et de ses tournois (arts martiaux / judo). Le projet est composé de trois briques qui communiquent entre elles :

- **`api/`** — API REST (Symfony 7 + MySQL) qui expose toutes les données et la logique métier.
- **`backoffice/`** — Application web (Symfony 7 + Twig) destinée aux administrateurs du club : consomme l'API via un client HTTP.
- **`mobile/`** — Application Android (Java + Retrofit) destinée aux adhérents : consomme la même API.

## Architecture générale

```
          ┌───────────────┐          ┌───────────────┐
          │  Backoffice   │          │  Mobile App   │
          │ (Symfony/Twig)│          │   (Android)   │
          └──────┬────────┘          └───────┬───────┘
                 │ HTTP + Bearer token       │ HTTP + Bearer token
                 │ (ApiClient.php)           │ (Retrofit / ApiService)
                 └────────────┬──────────────┘
                              ▼
                     ┌────────────────┐
                     │   API REST     │
                     │   (Symfony 7)  │
                     └────────┬───────┘
                              ▼
                        ┌──────────┐
                        │  MySQL   │
                        └──────────┘
```

Toute la logique métier (inscriptions, poules, matchs, scores, classements, plannings de tatamis) vit dans l'API. Le backoffice et le mobile ne sont que des clients.

## Authentification

- L'API expose `POST /api/login` qui renvoie un **token Bearer**.
- Le token est ensuite envoyé dans l'en-tête `Authorization: Bearer <token>` sur toutes les requêtes protégées.
- **Backoffice** : le token est stocké en session côté serveur (`ApiClient::setToken`) et ré-injecté à chaque appel.
- **Mobile** : le token est stocké localement et ajouté par un intercepteur Retrofit (`ApiClient.java`).
- L'API valide les tokens via `ApiTokenAuthenticator` (`api/src/Security/`).

Deux rôles principaux existent :
- `ROLE_ADMIN` — utilisé par le backoffice (gestion complète du club).
- `ROLE_USER` — adhérent, utilisé par l'app mobile (accès limité à ses propres données via les endpoints `/api/me/...`).

## Modèle de données

Entités principales (cf. `api/src/Entity/`) :

- **User** — compte de connexion (email / mot de passe / rôle), lié à un `Adherent`.
- **Adherent** — fiche d'un membre du club (nom, prénom, date de naissance, ceinture, poids, date d'adhésion).
- **Tournoi** — un événement, avec un état (`ouvert`, `en_cours`, `termine`, `annule`) et un `Parametre` (temps de combat, nb max de participants, taille des poules, nb de surfaces/tatamis…).
- **Participant** — inscription d'un adhérent à un tournoi (classement poule/tournoi, points, paiement, équipe éventuelle).
- **Equipe** — regroupement de participants pour les tournois par équipes.
- **Poule** — groupe de participants à l'intérieur d'un tournoi.
- **MatchTour** — un match, appartenant à une poule ou phase d'élimination, avec un créneau de planning (tatami + horaire).
- **Score** — résultat d'un participant dans un match (score, gagnant, disqualification).

Les plannings/tatamis sont gérés directement au niveau du `MatchTour` (pas d'entité séparée), et l'API vérifie les conflits de créneaux sur un même tatami.

## Le backoffice (administrateurs)

Application Symfony/Twig servie à part. Toutes ses pages passent par `App\Service\ApiClient` pour lire/écrire les données via l'API.

Principales sections :
- **Dashboard** (`/`) — vue d'ensemble du club.
- **Adhérents** (`/adherents`) — CRUD complet, renouvellement d'adhésion.
- **Tournois** (`/tournois`) — création/édition, gestion des états, inscriptions, paiements, poules, équipes, génération et planification des matchs, saisie des scores, gestion de la phase finale (élimination).
- **Matchs** (`/matchs`) — consultation globale des matchs.
- **Comptes** (`/comptes`) — gestion des utilisateurs et de leurs rôles.
- **Login** (`/login`) — authentification via l'API.

## L'app mobile (adhérents)

App Android native (Java) utilisant Retrofit + Gson. Toutes les communications passent par `ApiService` (`mobile/app/src/main/java/com/example/mobile/api/`).

Écrans principaux :
- **Login** — authentification et récupération du token (`POST /api/login`).
- **Profile** — affichage et mise à jour des infos de l'adhérent (`GET /api/me`, `PUT /api/me/adherent`).
- **Tournois** — liste de tous les tournois ouverts (`GET /api/tournois`).
- **Mes tournois** — tournois auxquels l'adhérent est inscrit, inscription/désinscription, choix d'équipe (`/api/me/tournois*`).
- **Matchs** — matchs de l'adhérent pour un tournoi donné (`GET /api/me/tournois/{id}/matchs`).
- **Scores** — historique des scores de l'adhérent (`GET /api/me/scores`).

## Installation rapide

### API
```bash
cd api
composer install
# configurer DATABASE_URL dans .env.local
php bin/console doctrine:migrations:migrate
symfony serve   # ou via Laragon : http://localhost/tournois-app-web/api/public
```

### Backoffice
```bash
cd backoffice
composer install
# configurer l'URL de l'API dans services.yaml ou .env (variable apiBaseUrl)
symfony serve
```

### Mobile
Ouvrir le dossier `mobile/` dans Android Studio, ajuster l'URL de base dans `ApiClient.java` si besoin, puis lancer sur un émulateur/appareil.

## Documentation complémentaire

- **`api/README.md`** — référence rapide de l'API REST (endpoints, formats).
- **`IMPROVEMENTS.md` / `MATCH_CREATION_IMPROVEMENTS.md`** — notes d'évolution.
