# Backend Dashboard (Laravel)

Backend API pour un dashboard de gestion de projets et tâches, avec gestion des rôles (admin/manager/employé), demandes d’inscription approuvées par email, notifications applicatives, exports PDF/Excel et un endpoint de chat via Google Gemini.

## Stack technique

- Laravel 10 + PHP >= 8.1 ([composer.json](./composer.json))
- Auth API par tokens via Laravel Sanctum
- Base de données: MySQL (par défaut)
- PDF: barryvdh/laravel-dompdf
- Excel: maatwebsite/excel
- Frontend attendu en dev: http://localhost:5173 (CORS)

## Fonctionnalités

- Authentification: register/login/logout + récupération utilisateur courant
- Profils: mise à jour des infos + upload photo de profil
- Projets (manager/admin/employee):
  - Admin: voit tous les projets
  - Manager: voit et gère ses projets
  - Employé: voit uniquement les projets où il a des tâches assignées
- Tâches:
  - CRUD côté manager (uniquement sur ses projets)
  - Consultation côté employé (uniquement ses tâches)
  - Dépendances: une tâche peut référencer une tâche précédente (previous_task_id)
- Notifications applicatives (table app_notifications): lecture, comptage, suppression
- Demandes métier:
  - Demande de suppression de compte (employé → admin)
  - Demande d’annulation de tâche (employé → manager)
  - Demande de changement de statut (employé → manager)
- Emails:
  - Demande d’inscription notifie les admins
  - Admin approuve/rejette, utilisateur reçoit un email et définit son mot de passe initial
  - Réinitialisation mot de passe via code à 6 chiffres (valide 15 min)
- Exports (manager uniquement):
  - PDF: un projet, tous les projets, projets + employés
  - Excel: un projet, tous les projets
- IA:
  - Endpoint public /api/chat relié à Google Gemini (clé API via .env)

## Architecture (où trouver quoi)

- Routes API: [routes/api.php](./routes/api.php)
- Contrôleurs: [app/Http/Controllers](./app/Http/Controllers)
- Modèles: [app/Models](./app/Models)
- Notifications email: [app/Notifications](./app/Notifications) + [resources/views/emails](./resources/views/emails)
- Exports PDF: [resources/views/pdf](./resources/views/pdf)
- Middleware rôles: [app/Http/Middleware/CheckRole.php](./app/Http/Middleware/CheckRole.php)

## Installation (local)

### Prérequis

- PHP >= 8.1
- Composer
- MySQL (ou MariaDB)

### Étapes

Depuis le dossier qui contient `artisan`:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Créer une base MySQL vide, puis configurer `.env` (voir exemple plus bas) et lancer:

```bash
php artisan migrate
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

L’API est alors accessible sur:

- http://127.0.0.1:8000/api

## Configuration `.env` (exemple)

Ne copie pas d’exemples avec de vraies clés. Mets tes propres valeurs.

```env
APP_NAME="Backend Dashboard"
APP_ENV=local
APP_KEY=base64:GENERATED_BY_ARTISAN
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=backend_dashboard
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_user
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"

GEMINI_API_KEY=YOUR_GOOGLE_GEMINI_API_KEY
```

### CORS (frontend)

Le backend autorise par défaut uniquement `http://localhost:5173` via [config/cors.php](./config/cors.php). Si ton frontend tourne ailleurs, adapte `allowed_origins`.

## Authentification (Sanctum)

Le login renvoie un token. Ensuite, toutes les routes protégées attendent un header:

```
Authorization: Bearer <token>
```

## Démarrage rapide (curl)

### Créer un admin (dev)

Le endpoint `/api/register` accepte `role` (user/manager/admin). En dev, tu peux créer un admin comme ceci:

```bash
curl -X POST "http://127.0.0.1:8000/api/register" -H "Content-Type: application/json" -d "{\"name\":\"Admin\",\"email\":\"admin@example.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"role\":\"admin\"}"
```

### Login

```bash
curl -X POST "http://127.0.0.1:8000/api/login" -H "Content-Type: application/json" -d "{\"email\":\"admin@example.com\",\"password\":\"password123\"}"
```

Récupère la valeur `token` dans la réponse JSON.

### Appeler une route protégée

```bash
curl "http://127.0.0.1:8000/api/projects" -H "Authorization: Bearer <token>"
```

### Mettre à jour le profil (avec photo)

```bash
curl -X PUT "http://127.0.0.1:8000/api/profile" -H "Authorization: Bearer <token>" -F "name=Nouveau Nom" -F "photo_de_profile=@C:\\chemin\\vers\\image.jpg"
```

## Workflows métier (comment ça fonctionne)

### Demande d’inscription (utilisateur → admin → utilisateur)

1. Public: l’utilisateur envoie POST `/api/request-registration`
2. Le backend notifie tous les admins par email (il faut au moins 1 admin existant)
3. L’admin approuve/rejette via l’API (auth) ou via un lien direct reçu par email
4. Si approuvé: un compte utilisateur est créé avec un mot de passe temporaire + un token
5. L’utilisateur définit son mot de passe via POST `/api/set-initial-password`

### Réinitialisation mot de passe (code)

1. POST `/api/forgot-password` envoie un code à 6 chiffres par email
2. POST `/api/verify-reset-code` valide le code (durée: 15 minutes)
3. POST `/api/reset-password` change le mot de passe et invalide le code

### Demande suppression de compte (utilisateur → admin)

1. Auth: POST `/api/account/delete-request` crée une demande `pending`
2. Les admins reçoivent une notification applicative (table `app_notifications`)
3. Admin approuve: le compte est supprimé
4. Admin rejette: l’utilisateur reçoit une notification avec la raison

### Demande annulation de tâche (employé → manager)

1. Auth: POST `/api/task-cancellation-request/{taskId}` (uniquement si la tâche est assignée à l’employé)
2. Le manager reçoit une notification applicative
3. Manager approuve: la tâche est supprimée; rejet: notification côté employé

### Demande changement de statut (employé → manager)

1. Auth: POST `/api/task-status-change-request/{taskId}` avec `requested_status` (en_attente/en_cours/terminé)
2. Le manager approuve/rejette via les endpoints dédiés
3. Si approuvé: la tâche est mise à jour

## Endpoints principaux (résumé)

Base: `/api`

### Public

- POST `/register` (crée un user directement)
- POST `/login`
- POST `/chat` (Gemini)
- POST `/request-registration` (demande d’inscription, notifie les admins)
- POST `/set-initial-password` (définir le mot de passe après approbation)
- POST `/forgot-password`
- POST `/verify-reset-code`
- POST `/reset-password`
- GET `/admin/approve-registration-direct/{id}/{token}` (depuis email)
- GET `/admin/reject-registration-form/{id}/{token}` (form HTML)
- POST `/admin/reject-registration-direct/{id}/{token}` (depuis email)
- GET `/set-password/{token}` (page HTML)

### Authentifié (auth:sanctum)

- POST `/logout`
- GET `/user`
- PUT `/profile` (multipart/form-data si photo)

**Notifications**

- GET `/notifications`
- GET `/notifications/unread`
- GET `/notifications/count`
- PUT `/notifications/{id}/read`
- PUT `/notifications/read-all`
- DELETE `/notifications/{id}`
- DELETE `/notifications`

**Projets & tâches (lecture)**

- GET `/projects`
- GET `/projects/{id}`
- GET `/projects/{projectId}/tasks`
- GET `/projects/{projectId}/tasks/{taskId}`
- GET `/employee/tasks`
- GET `/employees/stats`
- GET `/managers/stats`

**Demandes**

- POST `/account/delete-request`
- GET `/account/delete-request/status`
- POST `/task-cancellation-request/{taskId}`
- POST `/task-status-change-request/{taskId}`

### Admin uniquement (middleware role:admin)

- GET `/admin/pending-registrations`
- GET `/admin/registration/{id}`
- POST `/admin/approve-registration/{id}`
- POST `/admin/reject-registration/{id}`

Gestion demandes suppression de compte:

- GET `/admin/delete-requests`
- GET `/admin/delete-requests/pending`
- GET `/admin/delete-requests/{id}`
- POST `/admin/delete-requests/{id}/approve`
- POST `/admin/delete-requests/{id}/reject`
- DELETE `/admin/delete-requests/users/{id}`

### Manager uniquement (middleware role:manager)

Projets:

- POST `/projects`
- PUT `/projects/{id}`
- DELETE `/projects/{id}`
- GET `/assign-user` (liste employés)

Tâches:

- POST `/projects/{projectId}/tasks`
- PUT `/projects/{projectId}/tasks/{taskId}`
- DELETE `/projects/{projectId}/tasks/{taskId}`
- GET `/manager/tasks`

Exports:

- GET `/export/projects/{projectId}/pdf`
- GET `/export/projects/pdf`
- GET `/export/projects-with-users`
- GET `/export/excel/project/{projectId}`
- GET `/export/excel/projects`

Demandes (côté manager):

- POST `/manager/task-cancellations/{id}/approve`
- POST `/manager/task-cancellations/{id}/reject`
- POST `/task-status-change-request/{id}/approve`
- POST `/task-status-change-request/{id}/reject`

## Modèle de données (résumé)

- `users`: name, prenom, email, password, role (user/manager/admin), telephone, adresse, departement, photo_de_profile, bio
- `projects`: name, description, start_date, end_date, status (en_attente/en_cours/terminé), manager_id
- `tasks`: name, description, start_date, end_date, status, project_id, assigned_to, previous_task_id
- `app_notifications`: user_id, title, message, type, read, data (json), action_url
- `registration_requests`: name, prenom, email, role, phone, status, rejection_reason, token, approval_token
- `delete_account_requests`: user_id, reason, status, processed_by, processed_at, rejection_reason
- `task_cancellation_requests`: task_id, user_id, name, reason, status, processed_by, processed_at, rejection_reason
- `task_status_change_requests`: task_id, user_id, requested_status, status, processed_by, processed_at

## Points d’attention (à connaître)

- Pour que la demande d’inscription notifie quelqu’un, il faut au moins un admin existant en base (users.role=admin).
- Certaines migrations sont en doublon (ex: `app_notifications`, `delete_account_requests`, champs `adresse/departement`), ce qui peut faire échouer `php artisan migrate` sur une base neuve.
- Un contrôleur de chat alternatif contient une clé hardcodée (à éviter). Il n’est pas exposé par les routes, mais la clé doit être révoquée et déplacée vers `.env` si vous l’utilisez.
