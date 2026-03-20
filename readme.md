# API REST — Gestion de Tournois

API REST développée avec **Symfony 7** pour la gestion d'un club sportif et de ses tournois.  
Base de données **MySQL**. CORS géré via NelmioCorsBundle.

---

## Base URL

```
http://localhost/api
```

---

## Format des données

- Toutes les requêtes et réponses utilisent le format **JSON**
- Header requis pour les requêtes POST/PUT/PATCH : `Content-Type: application/json`

---

## Codes de statut HTTP

| Code | Signification |
|------|---------------|
| `200` | Succès |
| `201` | Ressource créée |
| `400` | Données invalides / champs manquants |
| `404` | Ressource non trouvée |
| `409` | Conflit (ex: doublon d'inscription) |
| `422` | Erreur de validation |
| `500` | Erreur serveur |

---

## Ressources

### 1. Adhérents `/api/adherents`

#### GET /api/adherents
Retourne la liste de tous les adhérents.

**Réponse 200 :**
```json
[
  {
    "id": 1,
    "nom": "Dupont",
    "prenom": "Jean",
    "date_naissance": "2000-05-15",
    "date_adhesion": "2024-09-01",
    "ceinture": "noire",
    "poids": "75.50"
  }
]
```

---

#### GET /api/adherents/{id}
Retourne un adhérent par son ID.

**Réponse 200 :** Objet adhérent  
**Réponse 404 :** `{ "message": "Adhérent non trouvé" }`

---

#### POST /api/adherents
Crée un nouvel adhérent.

**Corps de la requête :**
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "date_naissance": "2000-05-15",
  "ceinture": "noire",
  "poids": 75.50
}
```

> `date_adhesion` est automatiquement définie à la date du jour si non fournie.

**Réponse 201 :** Objet adhérent créé

---

#### PUT /api/adherents/{id}
Met à jour un adhérent existant.

**Corps de la requête :** (tous les champs sont optionnels)
```json
{
  "nom": "Martin",
  "ceinture": "marron",
  "poids": 80.00
}
```

**Réponse 200 :** Objet adhérent mis à jour

---

#### PATCH /api/adherents/{id}/renouvellement
Renouvelle l'adhésion d'un membre (met `date_adhesion` à aujourd'hui).

**Réponse 200 :** Objet adhérent mis à jour

---

#### DELETE /api/adherents/{id}
Supprime un adhérent.

**Réponse 200 :** `{ "message": "Adhérent supprimé" }`

---

### 2. Tournois `/api/tournois`

#### GET /api/tournois
Retourne tous les tournois avec leurs paramètres et poules.

**Réponse 200 :**
```json
[
  {
    "id": 1,
    "equipe": false,
    "date": "2025-03-15",
    "etat": "ouvert",
    "poules": [],
    "parametre": {
      "id": 1,
      "temps_combat": "5.00",
      "max_equipes": 0,
      "min_poule": 3,
      "max_participants": 32,
      "max_poule": 6,
      "nb_surfaces": 2
    }
  }
]
```

---

#### GET /api/tournois/{id}
Retourne un tournoi par son ID.

---

#### POST /api/tournois
Crée un nouveau tournoi. Les paramètres peuvent être créés en même temps.

**Corps de la requête :**
```json
{
  "equipe": false,
  "date": "2025-06-01",
  "etat": "ouvert",
  "parametre": {
    "temps_combat": "5.00",
    "max_equipes": 0,
    "min_poule": 3,
    "max_participants": 32,
    "max_poule": 6,
    "nb_surfaces": 2
  }
}
```

> États valides : `ouvert`, `en_cours`, `termine`, `annule`

**Réponse 201 :** Objet tournoi créé

---

#### PUT /api/tournois/{id}
Met à jour un tournoi.

**Corps de la requête :** (tous les champs sont optionnels)
```json
{
  "equipe": true,
  "date": "2025-07-01",
  "etat": "en_cours"
}
```

---

#### PATCH /api/tournois/{id}/etat
Change uniquement l'état d'un tournoi.

**Corps de la requête :**
```json
{
  "etat": "en_cours"
}
```

> États valides : `ouvert`, `en_cours`, `termine`, `annule`

**Réponse 200 :** `{ "message": "État mis à jour", "etat": "en_cours" }`

---

#### DELETE /api/tournois/{id}
Supprime un tournoi et ses paramètres associés.

---

#### GET /api/tournois/{id}/participants
Retourne tous les participants d'un tournoi.

---

#### GET /api/tournois/{id}/poules
Retourne toutes les poules d'un tournoi.

---

### 3. Participants `/api/participants`

#### GET /api/participants
Retourne tous les participants.

**Réponse 200 :**
```json
[
  {
    "id": 1,
    "rang_poule": null,
    "rang_tournoi": null,
    "points_tournoi": null,
    "poule": null,
    "adherent": {
      "nom": "Dupont",
      "prenom": "Jean",
      "ceinture": "noire",
      "poids": "75.50"
    },
    "equipe": null
  }
]
```

---

#### GET /api/participants/{id}
Retourne un participant par son ID.

---

#### POST /api/participants
Inscrit un adhérent à un tournoi.

**Corps de la requête :**
```json
{
  "adherent_id": 1,
  "tournoi_id": 2,
  "equipe_id": null
}
```

> Retourne `409` si l'adhérent est déjà inscrit à ce tournoi.

**Réponse 201 :** Objet participant créé

---

#### PUT /api/participants/{id}
Met à jour les informations d'un participant (rang, points, poule).

**Corps de la requête :**
```json
{
  "rang_poule": 1,
  "rang_tournoi": 3,
  "points_tournoi": "12.50",
  "poule": 1,
  "equipe_id": 2
}
```

---

#### DELETE /api/participants/{id}
Désinscrit un participant d'un tournoi.

---

#### GET /api/participants/{id}/scores
Retourne tous les scores d'un participant.

---

### 4. Poules `/api/poules`

#### GET /api/poules
Retourne toutes les poules.

---

#### GET /api/poules/{id}
Retourne une poule par son ID.

---

#### POST /api/poules
Crée une nouvelle poule pour un tournoi.

**Corps de la requête :**
```json
{
  "tournoi_id": 1,
  "categorie": "A"
}
```

**Réponse 201 :** Objet poule créé

---

#### PUT /api/poules/{id}
Met à jour la catégorie d'une poule.

**Corps de la requête :**
```json
{
  "categorie": "B"
}
```

---

#### DELETE /api/poules/{id}
Supprime une poule.

---

#### GET /api/poules/{id}/matchs
Retourne tous les matchs d'une poule.

---

### 5. Matchs `/api/matchs`

#### GET /api/matchs
Retourne tous les matchs.

---

#### GET /api/matchs/{id}
Retourne un match par son ID.

---

#### POST /api/matchs
Crée un nouveau match dans une poule.

**Corps de la requête :**
```json
{
  "poule_id": 1
}
```

**Réponse 201 :** Objet match créé

---

#### DELETE /api/matchs/{id}
Supprime un match.

---

#### GET /api/matchs/{id}/scores
Retourne les scores d'un match.

---

#### GET /api/matchs/{id}/planning
Retourne le planning d'un match.

---

### 6. Scores `/api/scores`

#### GET /api/scores
Retourne tous les scores.

---

#### GET /api/scores/{id}
Retourne un score par son ID.

---

#### POST /api/scores
Crée un score pour un participant dans un match.

**Corps de la requête :**
```json
{
  "participant_id": 1,
  "match_id": 3,
  "score": 10,
  "gagnant": true,
  "disqualification": false
}
```

**Réponse 201 :** Objet score créé

---

#### PUT /api/scores/{id}
Met à jour un score existant.

**Corps de la requête :**
```json
{
  "score": 15,
  "gagnant": false,
  "disqualification": true
}
```

---

#### DELETE /api/scores/{id}
Supprime un score.

---

### 7. Équipes `/api/equipes`

#### GET /api/equipes
Retourne toutes les équipes.

---

#### GET /api/equipes/{id}
Retourne une équipe par son ID.

---

#### POST /api/equipes
Crée une nouvelle équipe.

**Corps de la requête :**
```json
{
  "rang_equipe": 0
}
```

---

#### PUT /api/equipes/{id}
Met à jour le rang d'une équipe.

**Corps de la requête :**
```json
{
  "rang_equipe": 2
}
```

---

#### DELETE /api/equipes/{id}
Supprime une équipe.

---

#### GET /api/equipes/{id}/participants
Retourne tous les participants d'une équipe.

---

### 8. Plannings `/api/plannings`

#### GET /api/plannings
Retourne tous les plannings.

---

#### GET /api/plannings/{id}
Retourne un planning par son ID.

---

#### POST /api/plannings
Crée un planning pour un match sur un tatami.

**Corps de la requête :**
```json
{
  "heure_debut": "09:00:00",
  "heure_fin": "09:15:00",
  "tatami_id": 1,
  "match_id": 3
}
```

**Réponse 201 :** Objet planning créé

---

#### PUT /api/plannings/{id}
Met à jour un planning.

**Corps de la requête :**
```json
{
  "heure_debut": "10:00:00",
  "heure_fin": "10:15:00",
  "tatami_id": 2,
  "match_id": 4
}
```

---

#### DELETE /api/plannings/{id}
Supprime un planning.

---

### 9. Tatamis `/api/tatamis`

#### GET /api/tatamis
Retourne tous les tatamis.

---

#### GET /api/tatamis/{id}
Retourne un tatami par son ID.

---

#### POST /api/tatamis
Crée un nouveau tatami. Aucun corps requis.

**Réponse 201 :** `{ "id": 1 }`

---

#### DELETE /api/tatamis/{id}
Supprime un tatami.

---

## Tableau récapitulatif des routes

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/api/adherents` | Liste des adhérents |
| GET | `/api/adherents/{id}` | Détail d'un adhérent |
| POST | `/api/adherents` | Créer un adhérent |
| PUT | `/api/adherents/{id}` | Modifier un adhérent |
| PATCH | `/api/adherents/{id}/renouvellement` | Renouveler l'adhésion |
| DELETE | `/api/adherents/{id}` | Supprimer un adhérent |
| GET | `/api/tournois` | Liste des tournois |
| GET | `/api/tournois/{id}` | Détail d'un tournoi |
| POST | `/api/tournois` | Créer un tournoi |
| PUT | `/api/tournois/{id}` | Modifier un tournoi |
| PATCH | `/api/tournois/{id}/etat` | Changer l'état |
| DELETE | `/api/tournois/{id}` | Supprimer un tournoi |
| GET | `/api/tournois/{id}/participants` | Participants d'un tournoi |
| GET | `/api/tournois/{id}/poules` | Poules d'un tournoi |
| GET | `/api/participants` | Liste des participants |
| GET | `/api/participants/{id}` | Détail d'un participant |
| POST | `/api/participants` | Inscrire à un tournoi |
| PUT | `/api/participants/{id}` | Modifier un participant |
| DELETE | `/api/participants/{id}` | Désinscrire |
| GET | `/api/participants/{id}/scores` | Scores d'un participant |
| GET | `/api/poules` | Liste des poules |
| GET | `/api/poules/{id}` | Détail d'une poule |
| POST | `/api/poules` | Créer une poule |
| PUT | `/api/poules/{id}` | Modifier une poule |
| DELETE | `/api/poules/{id}` | Supprimer une poule |
| GET | `/api/poules/{id}/matchs` | Matchs d'une poule |
| GET | `/api/matchs` | Liste des matchs |
| GET | `/api/matchs/{id}` | Détail d'un match |
| POST | `/api/matchs` | Créer un match |
| DELETE | `/api/matchs/{id}` | Supprimer un match |
| GET | `/api/matchs/{id}/scores` | Scores d'un match |
| GET | `/api/matchs/{id}/planning` | Planning d'un match |
| GET | `/api/scores` | Liste des scores |
| GET | `/api/scores/{id}` | Détail d'un score |
| POST | `/api/scores` | Créer un score |
| PUT | `/api/scores/{id}` | Modifier un score |
| DELETE | `/api/scores/{id}` | Supprimer un score |
| GET | `/api/equipes` | Liste des équipes |
| GET | `/api/equipes/{id}` | Détail d'une équipe |
| POST | `/api/equipes` | Créer une équipe |
| PUT | `/api/equipes/{id}` | Modifier une équipe |
| DELETE | `/api/equipes/{id}` | Supprimer une équipe |
| GET | `/api/equipes/{id}/participants` | Membres d'une équipe |
| GET | `/api/plannings` | Liste des plannings |
| GET | `/api/plannings/{id}` | Détail d'un planning |
| POST | `/api/plannings` | Créer un planning |
| PUT | `/api/plannings/{id}` | Modifier un planning |
| DELETE | `/api/plannings/{id}` | Supprimer un planning |
| GET | `/api/tatamis` | Liste des tatamis |
| GET | `/api/tatamis/{id}` | Détail d'un tatami |
| POST | `/api/tatamis` | Créer un tatami |
| DELETE | `/api/tatamis/{id}` | Supprimer un tatami |