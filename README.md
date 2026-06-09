# Backend Dashboard (Laravel)

Backend API for a project & task management dashboard, with role-based access control (admin/manager/employee), email-approved registration requests, in-app notifications, PDF/Excel exports, and a chat endpoint powered by Google Gemini.

## Tech stack

- Laravel 10 + PHP >= 8.1 ([composer.json](./composer.json))
- Token-based API auth with Laravel Sanctum
- Database: MySQL (default)
- PDF: barryvdh/laravel-dompdf
- Excel: maatwebsite/excel
- Expected dev frontend origin: http://localhost:5173 (CORS)

## Features

- Authentication: register/login/logout + current user endpoint
- Profile: update user info + profile image upload
- Projects (manager/admin/employee):
  - Admin: sees all projects
  - Manager: sees and manages their own projects
  - Employee: only sees projects where they have assigned tasks
- Tasks:
  - CRUD for managers (only for their projects)
  - Read-only for employees (only their own tasks)
  - Dependencies: a task can reference a previous task (previous_task_id)
- In-app notifications (app_notifications table): list, count, mark as read, delete
- Business requests:
  - Account deletion request (employee → admin)
  - Task cancellation request (employee → manager)
  - Task status change request (employee → manager)
- Emails:
  - Registration request notifies admins
  - Admin approves/rejects; user receives an email and sets an initial password
  - Password reset using a 6-digit code (valid for 15 minutes)
- Exports (manager only):
  - PDF: single project, all projects, projects + employees
  - Excel: single project, all projects
- AI:
  - Public `/api/chat` endpoint connected to Google Gemini (API key via `.env`)

## Project structure (where to find what)

- API routes: [routes/api.php](./routes/api.php)
- Controllers: [app/Http/Controllers](./app/Http/Controllers)
- Models: [app/Models](./app/Models)
- Email notifications: [app/Notifications](./app/Notifications) + [resources/views/emails](./resources/views/emails)
- PDF export templates: [resources/views/pdf](./resources/views/pdf)
- Role middleware: [app/Http/Middleware/CheckRole.php](./app/Http/Middleware/CheckRole.php)

## Local setup

### Requirements

- PHP >= 8.1
- Composer
- MySQL (or MariaDB)

### Steps

From the folder that contains `artisan`:

```bash
composer install
```

Create your `.env`:

- Windows:

```bash
copy .env.example .env
```

- macOS/Linux:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Create an empty MySQL database, configure `.env` (see example below), then run:

```bash
php artisan migrate
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

The API will be available at:

- http://127.0.0.1:8000/api

## `.env` configuration (example)

Do not copy examples with real secrets. Use your own values.

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

By default, the backend only allows `http://localhost:5173` in [config/cors.php](./config/cors.php). If your frontend runs on a different origin, update `allowed_origins`.

## Authentication (Sanctum)

Login returns a token. All protected routes expect the header:

```
Authorization: Bearer <token>
```

## Quick start (curl)

### Create an admin (dev)

The `/api/register` endpoint accepts a `role` (user/manager/admin). In development, you can create an admin like this:

```bash
curl -X POST "http://127.0.0.1:8000/api/register" -H "Content-Type: application/json" -d "{\"name\":\"Admin\",\"email\":\"admin@example.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"role\":\"admin\"}"
```

### Login

```bash
curl -X POST "http://127.0.0.1:8000/api/login" -H "Content-Type: application/json" -d "{\"email\":\"admin@example.com\",\"password\":\"password123\"}"
```

Extract the `token` value from the JSON response.

### Call a protected route

```bash
curl "http://127.0.0.1:8000/api/projects" -H "Authorization: Bearer <token>"
```

### Update profile (with image)

```bash
curl -X PUT "http://127.0.0.1:8000/api/profile" -H "Authorization: Bearer <token>" -F "name=New Name" -F "photo_de_profile=@C:\\path\\to\\image.jpg"
```

## Business flows (how it works)

### Registration request (user → admin → user)

1. Public: user sends POST `/api/request-registration`
2. The backend emails all admins (you need at least one existing admin user)
3. Admin approves/rejects either via the authenticated API or via a direct email link
4. If approved: a user account is created with a temporary password + token
5. User sets their password via POST `/api/set-initial-password`

### Password reset (code)

1. POST `/api/forgot-password` sends a 6-digit code by email
2. POST `/api/verify-reset-code` validates the code (valid for 15 minutes)
3. POST `/api/reset-password` updates the password and invalidates the code

### Account deletion request (user → admin)

1. Auth: POST `/api/account/delete-request` creates a `pending` request
2. Admins receive an in-app notification (app_notifications table)
3. Admin approves: the account is deleted
4. Admin rejects: the user receives an in-app notification with the reason

### Task cancellation request (employee → manager)

1. Auth: POST `/api/task-cancellation-request/{taskId}` (only if the task is assigned to the employee)
2. The manager receives an in-app notification
3. Manager approves: the task is deleted; if rejected: employee is notified

### Task status change request (employee → manager)

1. Auth: POST `/api/task-status-change-request/{taskId}` with `requested_status` (en_attente/en_cours/terminé)
2. The manager approves/rejects using the dedicated endpoints
3. If approved: the task status is updated

## Main endpoints (summary)

Base: `/api`

### Public

- POST `/register` (creates a user directly)
- POST `/login`
- POST `/chat` (Gemini)
- POST `/request-registration` (registration request, emails admins)
- POST `/set-initial-password` (set password after approval)
- POST `/forgot-password`
- POST `/verify-reset-code`
- POST `/reset-password`
- GET `/admin/approve-registration-direct/{id}/{token}` (from email)
- GET `/admin/reject-registration-form/{id}/{token}` (HTML form)
- POST `/admin/reject-registration-direct/{id}/{token}` (from email)
- GET `/set-password/{token}` (HTML page)

### Authenticated (auth:sanctum)

- POST `/logout`
- GET `/user`
- PUT `/profile` (use multipart/form-data when uploading an image)

**Notifications**

- GET `/notifications`
- GET `/notifications/unread`
- GET `/notifications/count`
- PUT `/notifications/{id}/read`
- PUT `/notifications/read-all`
- DELETE `/notifications/{id}`
- DELETE `/notifications`

**Projects & tasks (read)**

- GET `/projects`
- GET `/projects/{id}`
- GET `/projects/{projectId}/tasks`
- GET `/projects/{projectId}/tasks/{taskId}`
- GET `/employee/tasks`
- GET `/employees/stats`
- GET `/managers/stats`

**Requests**

- POST `/account/delete-request`
- GET `/account/delete-request/status`
- POST `/task-cancellation-request/{taskId}`
- POST `/task-status-change-request/{taskId}`

### Admin only (role:admin middleware)

- GET `/admin/pending-registrations`
- GET `/admin/registration/{id}`
- POST `/admin/approve-registration/{id}`
- POST `/admin/reject-registration/{id}`

Account deletion requests:

- GET `/admin/delete-requests`
- GET `/admin/delete-requests/pending`
- GET `/admin/delete-requests/{id}`
- POST `/admin/delete-requests/{id}/approve`
- POST `/admin/delete-requests/{id}/reject`
- DELETE `/admin/delete-requests/users/{id}`

### Manager only (role:manager middleware)

Projects:

- POST `/projects`
- PUT `/projects/{id}`
- DELETE `/projects/{id}`
- GET `/assign-user` (list employees)

Tasks:

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

Requests (manager side):

- POST `/manager/task-cancellations/{id}/approve`
- POST `/manager/task-cancellations/{id}/reject`
- POST `/task-status-change-request/{id}/approve`
- POST `/task-status-change-request/{id}/reject`

## Data model (summary)

- `users`: name, prenom, email, password, role (user/manager/admin), telephone, adresse, departement, photo_de_profile, bio
- `projects`: name, description, start_date, end_date, status (en_attente/en_cours/terminé), manager_id
- `tasks`: name, description, start_date, end_date, status, project_id, assigned_to, previous_task_id
- `app_notifications`: user_id, title, message, type, read, data (json), action_url
- `registration_requests`: name, prenom, email, role, phone, status, rejection_reason, token, approval_token
- `delete_account_requests`: user_id, reason, status, processed_by, processed_at, rejection_reason
- `task_cancellation_requests`: task_id, user_id, name, reason, status, processed_by, processed_at, rejection_reason
- `task_status_change_requests`: task_id, user_id, requested_status, status, processed_by, processed_at

## Notes (important)

- For registration requests to email someone, you need at least one admin user in the database (users.role=admin).
- Some migrations are duplicated (e.g. `app_notifications`, `delete_account_requests`, `adresse/departement` fields), which can cause `php artisan migrate` to fail on a fresh database.
- An alternative chat controller contains a hardcoded API token (avoid this). It is not wired in the routes, but the token should be revoked and moved to `.env` if you decide to use it.

## Production deployment

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Set `APP_URL` (your domain) + SMTP configuration
- Run `php artisan migrate --force`
- Optional: `php artisan config:cache` and `php artisan route:cache`
