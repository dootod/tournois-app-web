# API REST — Tournois

API REST (Symfony 7 + MySQL) du projet. Consommée par le backoffice web et l'app mobile Android. Voir le `readme.md` à la racine du projet pour l'architecture générale.

## Base URL

```
http://localhost/tournois-app-web/api/public/api
```

## Format

- Requêtes / réponses en **JSON**.
- Header `Content-Type: application/json` sur POST/PUT/PATCH.
- Authentification par **Bearer token** : `Authorization: Bearer <token>` (obtenu via `POST /login`).

## Codes de statut

| Code | Signification |
|------|---------------|
| 200  | Succès |
| 201  | Ressource créée |
| 400  | Données invalides |
| 401  | Non authentifié |
| 403  | Accès refusé |
| 404  | Introuvable |
| 409  | Conflit (doublon) |
| 422  | Erreur de validation |
| 500  | Erreur serveur |

## Authentification

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/login` | Login (body: `{ email, password }`) → renvoie un token |
| GET  | `/me`    | Utilisateur courant (token requis) |

## Endpoints « Me » (app mobile, adhérent connecté)

| Méthode | Route | Description |
|---------|-------|-------------|
| GET    | `/me/tournois` | Tournois auxquels je suis inscrit |
| GET    | `/me/tournois/{id}/equipes` | Équipes d'un tournoi |
| GET    | `/me/tournois/{id}/matchs` | Mes matchs dans un tournoi |
| POST   | `/me/tournois/{id}/inscription` | S'inscrire à un tournoi |
| DELETE | `/me/tournois/{id}/inscription` | Se désinscrire |
| GET    | `/me/scores` | Mes scores |
| PUT    | `/me/adherent` | Mettre à jour ma fiche adhérent |

## Ressources (administration — backoffice)

### Adhérents — `/adherents`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/adherents` | Liste |
| GET | `/adherents/{id}` | Détail |
| POST | `/adherents` | Créer |
| PUT | `/adherents/{id}` | Modifier |
| PATCH | `/adherents/{id}/renouvellement` | Renouveler l'adhésion |
| DELETE | `/adherents/{id}` | Supprimer |

### Tournois — `/tournois`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/tournois` | Liste |
| GET | `/tournois/{id}` | Détail |
| GET | `/tournois/{id}/show` | Détail complet (participants, poules, matchs) |
| POST | `/tournois` | Créer (avec `parametre`) |
| PUT | `/tournois/{id}` | Modifier |
| PATCH | `/tournois/{id}/etat` | Changer l'état (`ouvert`, `en_cours`, `termine`, `annule`) |
| DELETE | `/tournois/{id}` | Supprimer |
| GET | `/tournois/{id}/participants` | Participants |
| GET | `/tournois/{id}/poules` | Poules |
| GET | `/tournois/{id}/equipes` | Équipes |
| GET | `/tournois/{id}/matchs` | Matchs |
| GET | `/tournois/{id}/matchs/elimination` | Matchs de la phase finale |
| GET | `/tournois/{id}/check-tatami-conflict` | Vérifier un conflit de créneau |

### Participants — `/participants`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/participants` | Liste |
| GET | `/participants/{id}` | Détail |
| POST | `/participants` | Inscrire un adhérent (body: `adherent_id`, `tournoi_id`, `equipe_id?`) |
| PUT | `/participants/{id}` | Modifier (rang, points, poule, équipe) |
| PATCH | `/participants/{id}/paiement` | Marquer payé |
| DELETE | `/participants/{id}` | Désinscrire |
| GET | `/participants/{id}/scores` | Scores |

### Poules — `/poules`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/poules` | Liste |
| GET | `/poules/{id}` | Détail |
| POST | `/poules` | Créer (`tournoi_id`, `categorie`) |
| PUT | `/poules/{id}` | Modifier |
| DELETE | `/poules/{id}` | Supprimer |
| GET | `/poules/{id}/matchs` | Matchs de la poule |

### Matchs — `/matchs`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/matchs` | Liste |
| GET | `/matchs/{id}` | Détail |
| POST | `/matchs` | Créer |
| PUT | `/matchs/{id}` | Modifier (planning inclus) |
| DELETE | `/matchs/{id}` | Supprimer |
| GET | `/matchs/{id}/scores` | Scores du match |

### Scores — `/scores`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/scores` | Liste |
| GET | `/scores/{id}` | Détail |
| POST | `/scores` | Créer (`participant_id`, `match_id`, `score`, `gagnant`, `disqualification`) |
| PUT | `/scores/{id}` | Modifier |
| DELETE | `/scores/{id}` | Supprimer |

### Équipes — `/equipes`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/equipes/{id}` | Détail |
| POST | `/equipes` | Créer |
| PUT | `/equipes/{id}` | Modifier |
| DELETE | `/equipes/{id}` | Supprimer |
| POST | `/equipes/{id}/membres` | Ajouter un membre |
| DELETE | `/equipes/{id}/membres/{participantId}` | Retirer un membre |

### Utilisateurs — `/users`
| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/users` | Liste |
| GET | `/users/{id}` | Détail |
| POST | `/users` | Créer |
| PUT | `/users/{id}` | Modifier |
| DELETE | `/users/{id}` | Supprimer |

## Exemple d'appel

```bash
# Login
curl -X POST http://localhost/tournois-app-web/api/public/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@club.fr","password":"secret"}'

# Appel authentifié
curl http://localhost/tournois-app-web/api/public/api/tournois \
  -H "Authorization: Bearer <token>"
```

## Installation

```bash
composer install
# configurer DATABASE_URL dans .env.local
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load   # optionnel
```
