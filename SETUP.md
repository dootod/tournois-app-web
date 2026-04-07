# Installation du projet sur un poste vierge

Ce guide explique comment installer et lancer le projet **Tournois App** (API + Backoffice + App mobile) à partir de zéro, sur un PC où rien n'est installé. Le projet est développé sous Windows avec Laragon, les commandes ci-dessous partent de cette hypothèse.

---

## 1. Pré-requis à installer

### 1.1 Laragon (serveur local Apache + MySQL + PHP)
- Télécharger **Laragon Full** : https://laragon.org/download/
- Installer dans `C:\laragon`.
- Lancer Laragon puis **Start All** (Apache + MySQL démarrent).

> Laragon fournit déjà Apache, MySQL et PHP. Vérifier que la version de PHP est **≥ 8.2** (menu Laragon → PHP → Version). Sinon télécharger PHP 8.2+ via Laragon → PHP → Download More.

### 1.2 Composer
- Télécharger : https://getcomposer.org/download/
- Installer (le setup détecte automatiquement le PHP de Laragon).
- Vérifier :
  ```bash
  composer --version
  ```

### 1.3 Symfony CLI (optionnel mais recommandé)
- Télécharger : https://symfony.com/download
- Vérifier :
  ```bash
  symfony -V
  ```

### 1.4 Git
- Télécharger : https://git-scm.com/download/win
- Vérifier :
  ```bash
  git --version
  ```

### 1.5 Node.js (optionnel)
Uniquement si vous devez recompiler les assets du backoffice (AssetMapper) :
- Télécharger LTS : https://nodejs.org/

### 1.6 Android Studio (pour l'app mobile)
- Télécharger : https://developer.android.com/studio
- Pendant l'installation, accepter les SDK par défaut + un émulateur (par ex. Pixel API 34).

---

## 2. Récupérer le projet

Ouvrir un terminal dans `C:\laragon\www` :

```bash
cd C:\laragon\www
git clone <url-du-repo> tournois-app-web
cd tournois-app-web
```

Le projet contient trois dossiers :
- `api/` — API REST Symfony
- `backoffice/` — site d'administration Symfony
- `mobile/` — app Android

---

## 3. Base de données MySQL

1. Ouvrir **HeidiSQL** (fourni avec Laragon) ou phpMyAdmin.
2. Se connecter avec l'utilisateur `root` (sans mot de passe par défaut).
3. Créer une base **`tournois`** en `utf8mb4_unicode_ci`.

> La même base est utilisée par l'API et le backoffice.

---

## 4. Installer l'API

```bash
cd C:\laragon\www\tournois-app-web\api
composer install
```

Créer un fichier `.env.local` à côté du `.env` :

```dotenv
APP_ENV=dev
APP_SECRET=change_me
DATABASE_URL="mysql://root@127.0.0.1:3306/tournois?serverVersion=8.0.32&charset=utf8mb4"
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

Exécuter les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

(Optionnel) Charger des données de test :

```bash
php bin/console doctrine:fixtures:load
```

Avec Laragon, l'API est accessible à :
```
http://localhost/tournois-app-web/api/public/api
```

Tester :
```bash
curl http://localhost/tournois-app-web/api/public/api/tournois
```

---

## 5. Installer le backoffice

```bash
cd C:\laragon\www\tournois-app-web\backoffice
composer install
```

Créer un fichier `.env.local` :

```dotenv
APP_ENV=dev
APP_SECRET=change_me
API_BASE_URL=http://localhost/tournois-app-web/api/public/api
DATABASE_URL="mysql://root@127.0.0.1:3306/tournois?serverVersion=8.0.32&charset=utf8mb4"
```

Lancer le serveur Symfony :

```bash
symfony serve -d
```

Le backoffice est alors dispo sur `https://127.0.0.1:8000` (ou via Laragon sur `http://localhost/tournois-app-web/backoffice/public`).

Se connecter avec un compte admin créé via les fixtures, ou via l'API :
```bash
curl -X POST http://localhost/tournois-app-web/api/public/api/users \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@club.fr","password":"admin","roles":["ROLE_ADMIN"]}'
```

---

## 6. Installer l'app mobile

1. Ouvrir **Android Studio** → *Open* → sélectionner le dossier `mobile/`.
2. Laisser Gradle télécharger ses dépendances (première fois = long).
3. Ouvrir `mobile/app/src/main/java/com/example/mobile/api/ApiClient.java` et vérifier la constante :
   ```java
   public static final String BASE_URL = "http://10.0.2.2/tournois-app-web/api/public/api/";
   ```
   - `10.0.2.2` correspond au `localhost` du PC hôte vu depuis l'émulateur Android.
   - Si vous testez sur un appareil physique, remplacer par l'IP locale du PC (par ex. `http://192.168.1.50/...`).
4. Créer un émulateur (**Device Manager** → *Create Device*).
5. Cliquer sur **Run** ▶ pour lancer l'app.

---

## 7. Vérification finale

- [ ] `http://localhost/tournois-app-web/api/public/api/tournois` répond en JSON.
- [ ] Le backoffice s'ouvre et vous pouvez vous connecter.
- [ ] L'app mobile se connecte et affiche la liste des tournois.

Si un composant ne fonctionne pas :
- **API 500** : vérifier `api/var/log/dev.log`.
- **Backoffice « API unreachable »** : vérifier `API_BASE_URL` dans `.env.local`.
- **Mobile `Failed to connect`** : vérifier la `BASE_URL` et que le pare-feu Windows n'est pas en train de bloquer Apache.
