# Mise en production sur IONOS via FileZilla

Ce guide explique pas à pas comment déployer le projet **Tournois App** (API Symfony + Backoffice Symfony) sur un hébergement mutualisé **IONOS**, en transférant les fichiers avec **FileZilla**.

L'app mobile n'est pas concernée : il suffit juste de changer la `BASE_URL` dans le code Android pour pointer vers les URLs de prod et de publier le `.apk`.

---

## 1. Pré-requis IONOS

Depuis votre espace client IONOS, vous devez avoir :

1. **Un pack d'hébergement web** avec PHP ≥ 8.2 activé.
   - Menu *Hébergement* → *Gestion PHP* → sélectionner **PHP 8.2** ou plus pour le domaine.
2. **Un nom de domaine** (par ex. `mon-club.fr`).
   - Idéalement deux sous-domaines :
     - `api.mon-club.fr` → pour l'API
     - `admin.mon-club.fr` → pour le backoffice
   - À créer dans *Domaines & SSL* → *Sous-domaines*.
3. **Un certificat SSL** activé sur chaque (sous-)domaine (*Domaines & SSL* → *SSL*).
4. **Une base de données MySQL** :
   - Menu *Base de données* → *Créer une base de données MySQL*.
   - Noter : **hôte**, **nom de la base**, **utilisateur**, **mot de passe**.
5. **Un accès SFTP** :
   - Menu *Hébergement* → *SFTP & SSH* → créer un utilisateur SFTP.
   - Noter : **serveur (ex. `home123456789.1and1-data.host`)**, **login**, **mot de passe**, **port (22)**.

---

## 2. Préparer les fichiers en local

Avant d'uploader, on bascule les deux applis Symfony en **mode prod**.

### 2.1 API

```bash
cd api
composer install --no-dev --optimize-autoloader
```

Créer un fichier `.env.local` **de production** (⚠ ne pas commiter) :

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<une_chaine_aleatoire_longue>
DATABASE_URL="mysql://UTILISATEUR:MOTDEPASSE@HOTE_IONOS:3306/NOM_BASE?serverVersion=8.0&charset=utf8mb4"
CORS_ALLOW_ORIGIN='^https?://(admin\.mon-club\.fr|mon-club\.fr)$'
```

Vider et regénérer le cache :

```bash
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod
```

### 2.2 Backoffice

```bash
cd ../backoffice
composer install --no-dev --optimize-autoloader
```

`.env.local` de prod :

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<une_autre_chaine_aleatoire>
API_BASE_URL=https://api.mon-club.fr
DATABASE_URL="mysql://UTILISATEUR:MOTDEPASSE@HOTE_IONOS:3306/NOM_BASE?serverVersion=8.0&charset=utf8mb4"
```

Cache :

```bash
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod
php bin/console asset-map:compile   # compile les assets (AssetMapper)
```

### 2.3 Fichiers à NE PAS uploader

Avant d'envoyer, s'assurer qu'on n'enverra pas :
- `var/cache/dev/`, `var/log/`
- `.git/`
- `node_modules/` (s'il y en a)
- Les anciennes archives
- Les `.env.local` de **dev** (on uploadera ceux de prod)

---

## 3. Se connecter avec FileZilla

1. Ouvrir **FileZilla** → menu *Fichier* → *Gestionnaire de sites* → *Nouveau site*.
2. Remplir :
   - **Protocole** : `SFTP - SSH File Transfer Protocol`
   - **Hôte** : celui fourni par IONOS (ex. `home123456789.1and1-data.host`)
   - **Port** : `22`
   - **Type d'authentification** : `Normale`
   - **Identifiant** / **Mot de passe** : ceux du SFTP IONOS
3. Cliquer **Connexion**. Accepter la clé du serveur.

À gauche = votre PC, à droite = le serveur IONOS.

---

## 4. Arborescence cible sur IONOS

IONOS expose chaque (sous-)domaine sur un dossier. Dans *Domaines & SSL* → *Configurer le domaine*, faire pointer :

- `api.mon-club.fr` → dossier `/api/public`
- `admin.mon-club.fr` → dossier `/backoffice/public`

Arborescence recommandée sur le serveur :

```
/
├── api/               ← tout le code de l'API (hors public)
│   ├── bin/
│   ├── config/
│   ├── migrations/
│   ├── public/        ← racine web de api.mon-club.fr
│   ├── src/
│   ├── var/
│   ├── vendor/
│   ├── .env
│   └── .env.local
└── backoffice/
    ├── bin/
    ├── config/
    ├── public/        ← racine web de admin.mon-club.fr
    ├── src/
    ├── var/
    ├── vendor/
    ├── .env
    └── .env.local
```

> Important : le code (src, vendor, config…) doit être **au-dessus** du dossier `public`, pas à l'intérieur. Si IONOS ne permet pas de faire pointer un sous-domaine vers un sous-dossier autre que la racine, installer dans un dossier dédié et ajuster le `DocumentRoot` / sous-domaine en conséquence.

---

## 5. Upload des fichiers

Dans FileZilla, sélectionner à gauche le dossier `api/` du projet, puis à droite naviguer vers `/api/` sur IONOS, et **glisser-déposer**.

Faire pareil pour `backoffice/`.

Astuce :
- Clic droit sur un dossier → *Ajouter les fichiers à la file d'attente* pour préparer un gros upload.
- *Transferts → File d'attente → Traiter la file d'attente*.

Penser à uploader aussi les fichiers masqués (`.env`, `.env.local`, `.htaccess`) :
- Menu *Serveur* → *Forcer l'affichage des fichiers cachés*.

⏱ Un upload complet de `vendor/` prend du temps (milliers de petits fichiers). Laisser FileZilla tourner.

---

## 6. Permissions

Via l'onglet SSH d'IONOS (ou clic droit dans FileZilla → *Droits d'accès au fichier…*) :

```
var/      → 775 (récursif)
public/   → 755
.env.local → 600
```

---

## 7. Initialiser la base de données

1. Ouvrir *phpMyAdmin* depuis l'espace IONOS (*Base de données* → *Ouvrir*).
2. Sélectionner la base créée à l'étape 1.
3. Option A — via SSH IONOS (si vous avez accès SSH) :
   ```bash
   cd ~/api
   php bin/console doctrine:migrations:migrate --no-interaction --env=prod
   ```
4. Option B — pas d'accès SSH :
   - En local, générer le SQL :
     ```bash
     php bin/console doctrine:schema:create --dump-sql > schema.sql
     ```
     (ou exporter la base dev depuis HeidiSQL : *Exporter la base de données en SQL*)
   - Dans phpMyAdmin → *Importer* → sélectionner `schema.sql`.

Créer un premier compte admin (via phpMyAdmin ou via un appel `POST /users` à l'API de prod).

---

## 8. Tester en production

- `https://api.mon-club.fr/tournois` → doit renvoyer du JSON (`[]` si base vide).
- `https://admin.mon-club.fr` → page de login du backoffice.
- Se connecter avec le compte admin créé.

---

## 9. Mettre à jour l'app mobile

Dans `mobile/app/src/main/java/com/example/mobile/api/ApiClient.java` :

```java
public static final String BASE_URL = "https://api.mon-club.fr/";
```

Puis dans Android Studio :
- *Build → Generate Signed Bundle / APK* → *APK* → *release*.
- Distribuer l'`.apk` ou publier sur le Play Store.

---

## 10. Dépannage

| Symptôme | Cause probable | Solution |
|----------|----------------|----------|
| Erreur 500 sur l'API | `APP_ENV=dev`, cache pas vidé, permissions `var/` | Passer en `prod`, `chmod -R 775 var`, consulter `var/log/prod.log` |
| Page blanche backoffice | `APP_DEBUG=0` masque l'erreur | Regarder `backoffice/var/log/prod.log` |
| `SQLSTATE... access denied` | Mauvais `DATABASE_URL` | Revérifier host/user/password IONOS |
| CORS bloqué depuis le mobile | `CORS_ALLOW_ORIGIN` trop restrictif | Ajuster la regex dans `api/.env.local` |
| Mixed content | Appels en HTTP depuis page HTTPS | Toujours appeler l'API via `https://` |
| `.env.local` écrasé lors d'un ré-upload | FileZilla en mode *Écraser* | Décocher l'upload de `.env.local` lors des mises à jour |

---

## 11. Déploiements suivants

Pour une mise à jour ultérieure :

1. En local : `git pull`, `composer install --no-dev -o`, `cache:clear --env=prod`.
2. Dans FileZilla, uploader uniquement les dossiers modifiés (`src/`, `templates/`, `config/`, `migrations/`, éventuellement `vendor/` si dépendances modifiées).
3. **Ne pas** écraser les `.env.local` de prod.
4. Si nouvelles migrations : rejouer `doctrine:migrations:migrate` (via SSH ou phpMyAdmin).
5. Vider le cache prod : supprimer le contenu de `var/cache/prod/` via FileZilla.
