# PortGO API

Backend API for the PortGO project, built with Laravel 12, PHP 8+, and authentication through Laravel Sanctum.

This repository exposes endpoints for:

- user registration and login;
- password recovery and reset;
- profile retrieval and update;
- helper data listing (school classes, shifts, difficulties);
- question listing and filtering;
- user daily lesson streak tracking.

## Table of Contents

- [Stack and Requirements](#stack-and-requirements)
- [Application Architecture](#application-architecture)
- [Authentication and Security Flow](#authentication-and-security-flow)
- [Local Setup and Run](#local-setup-and-run)
- [Environment Variables](#environment-variables)
- [Data Model](#data-model)
- [API Documentation](#api-documentation)
- [Standard Errors and Responses](#standard-errors-and-responses)
- [Tests](#tests)
- [Useful Commands](#useful-commands)

## Stack and Requirements

- PHP `^8.2`
- Laravel Framework `^12`
- Laravel Sanctum `^4`
- PHPUnit `^11`
- Node.js + npm (Vite/Tailwind for assets)

Main dependencies are defined in `composer.json` and `package.json`.

## Application Architecture

The application follows Laravel's standard HTTP layered architecture:

1. **Routes** (`routes/api.php`)
   - Defines REST endpoints and route middlewares.
2. **Middleware** (`app/Http/Middleware/EnsureApiKeyIsValid.php`)
   - Validates the `X-API-KEY` header for all API routes.
3. **Validation Requests** (`app/Http/Requests/Api/*`)
   - Centralizes validation rules and messages per endpoint.
4. **Controllers** (`app/Http/Controllers/Api/*`)
   - Orchestrates HTTP input, model calls, and JSON output.
5. **Eloquent Models** (`app/Models/*`)
   - Encapsulates persistence and relationships.
6. **Database Migrations** (`database/migrations/*`)
   - Versioned schema plus initial seeded data.

### Authentication organization

- **First layer:** API Key (`X-API-KEY`) through `api.key` middleware.
- **Second layer (protected routes):** Sanctum Bearer Token (`auth:sanctum`).

### Directory structure (API-focused)

```text
app/
  Http/
    Controllers/Api/
      AuthController.php
      ProfileController.php
      SchoolClassController.php
      ShiftController.php
      DifficultyController.php
      QuestionController.php
    Middleware/
      EnsureApiKeyIsValid.php
    Requests/Api/
      RegisterRequest.php
      LoginRequest.php
      ForgotPasswordRequest.php
      ResetPasswordRequest.php
      UpdateProfileRequest.php
      ListQuestionsRequest.php
  Models/
    User.php
    SchoolClass.php
    Shift.php
    Difficulty.php
    Question.php
routes/
  api.php
database/
  migrations/
tests/
  Feature/Api/AuthApiTest.php
```

## Authentication and Security Flow

### 1) Required API Key

All routes in `routes/api.php` are inside the `api.key` middleware group.

Required header:

```http
X-API-KEY: {PORTGO_API_KEY}
```

If missing or invalid:

- HTTP `401`
- body:

```json
{
  "message": "API key inválida."
}
```

### 2) Sanctum Bearer token on protected routes

Protected routes:

- `GET /api/profile/{uuid}`
- `PATCH /api/profile`

Additional required header:

```http
Authorization: Bearer {token}
```

If unauthenticated:

- HTTP `401`
- body:

```json
{
  "message": "Não autenticado."
}
```

## Local Setup and Run

### 1) Install dependencies

```bash
composer install
npm install
```

### 2) Prepare environment

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, you can use:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### 3) Configure essential `.env` values

```dotenv
APP_NAME=PortGO
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:3000

PORTGO_API_KEY=your-key-here

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portgo
DB_USERNAME=root
DB_PASSWORD=
```

### 4) Build database schema

```bash
php artisan migrate
```

### 5) Run application

Simple mode:

```bash
php artisan serve
```

Full dev mode (server + queue + logs + vite):

```bash
composer run dev
```

## Environment Variables

Most important variables for API usage:

- `PORTGO_API_KEY`: key validated by API key middleware.
- `APP_URL`: backend base URL.
- `APP_FRONTEND_URL`: frontend URL used in password reset emails.
- `DB_*`: database connection.
- `MAIL_*`: password recovery email delivery.

## Data Model

### Main tables

#### `users`

Relevant API fields:

- `id` (PK)
- `uuid` (unique)
- `first_name`, `last_name`
- `email` (unique)
- `phone`, `state`, `city`, `school`
- `class` (FK -> `classes.id`, nullable)
- `shift` (FK -> `shifts.id`, nullable)
- `password`
- `created_at`, `updated_at`

#### `classes`

- `id`, `name`, timestamps
- initial seed with 7 options: `6º série` through `3º ano`

#### `shifts`

- `id`, `name`, timestamps
- initial seed: `Manhã`, `Tarde`, `Integral`

#### `difficulties`

- `id`, `name`, timestamps
- initial seed: `Fácil`, `Médio`, `Difícil`

#### `questions`

- `id`
- `statement`
- `alternative_a`, `alternative_b`, `alternative_c`, `alternative_d`
- `correct_alternative` (`a|b|c|d`)
- `tip`
- `difficulty_id` (FK -> `difficulties.id`)
- `class_id` (FK -> `classes.id`)
- timestamps

Seed data is generated in migration with `25` questions for each class × difficulty combination,
totaling `525` questions (7 classes × 3 difficulties × 25).

#### `user_streaks`

- `id`
- `user_id` (unique, FK -> `users.id`)
- `last_lesson_date` (nullable)
- `current_streak` (unsigned integer)
- `best_streak` (unsigned integer)
- `created_at`, `updated_at`

### Eloquent relationships

- `User` belongs to `SchoolClass` (`class`) and `Shift` (`shift`)
- `SchoolClass` has many `User` and `Question`
- `Shift` has many `User`
- `Difficulty` has many `Question`
- `Question` belongs to `Difficulty` and `SchoolClass`
- `User` has one `UserStreak`
- `UserStreak` belongs to `User`

## API Documentation

Base path: `/api`

Common headers (all routes):

```http
X-API-KEY: {PORTGO_API_KEY}
Accept: application/json
Content-Type: application/json
```

---

## Auth

### `POST /api/register`

Creates a new user.

Request body:

```json
{
  "first_name": "João",
  "last_name": "Silva",
  "email": "joao@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Validations:

- `first_name`: required, string, max:255
- `last_name`: required, string, max:255
- `email`: required, email, max:255, unique
- `password`: required, string, min:8, confirmed

Success (`201`):

```json
{
  "message": "Usuário cadastrado com sucesso.",
  "user": {
    "id": 1,
    "uuid": "generated-uuid",
    "first_name": "João",
    "last_name": "Silva",
    "email": "joao@example.com",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

Common errors:

- `401` invalid API key
- `422` validation error

### `POST /api/login`

Authenticates user and returns Sanctum token.

Request body:

```json
{
  "email": "joao@example.com",
  "password": "password123"
}
```

Validations:

- `email`: required, email
- `password`: required, string

Success (`200`):

```json
{
  "message": "Login realizado com sucesso.",
  "uuid": "user-uuid",
  "email": "joao@example.com",
  "profile_completed": false,
  "token": "1|token..."
}
```

`profile_completed` is calculated from:

- `first_name`, `last_name`, `email`, `phone`, `state`, `city`, `school`, `class`, `shift`

Common errors:

- `401` invalid credentials
- `422` (`{"message":"Os dados informados são inválidos.","errors":...}`)

### `POST /api/forgot-password`

Sends password reset email.

Request body:

```json
{
  "email": "joao@example.com"
}
```

Validations:

- `email`: required, email, exists:users,email

Success (`200`):

```json
{
  "message": "Link de redefinição enviado para o seu e-mail."
}
```

Failure (`500`):

```json
{
  "message": "Não foi possível enviar o link de redefinição."
}
```

Note: email uses `APP_FRONTEND_URL` (or `APP_URL`) to build:

`/reset-password?token={token}&email={email}`

### `POST /api/reset-password`

Resets password using token.

Request body (JSON):

```json
{
  "token": "received-token",
  "email": "joao@example.com",
  "password": "new-password-123",
  "password_confirmation": "new-password-123"
}
```

Validations:

- `token`: required
- `email`: required, email, exists
- `password`: required, string, min:8, confirmed

Success (`200`):

```json
{
  "message": "Senha redefinida com sucesso."
}
```

Token failure (`400`):

```json
{
  "message": "Token inválido ou expirado."
}
```

Validation errors (`422`):

- when `token` / `email` are not provided in JSON body.

---

## Profile

### `GET /api/users/{uuid}`

Returns user data by UUID.

Auth:

- requires `X-API-KEY`
- does **not** require bearer token

Success (`200`):

```json
{
  "user": {
    "id": 1,
    "uuid": "...",
    "name": "...",
    "first_name": "...",
    "last_name": "...",
    "email": "...",
    "phone": "...",
    "state": "...",
    "city": "...",
    "school": "...",
    "class": 1,
    "shift": 1,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### `GET /api/profile/{uuid}`

Returns authenticated user's profile, validating that route `uuid` belongs to the authenticated token.

Auth:

- requires `X-API-KEY`
- requires `Authorization: Bearer {token}`

Success (`200`):

```json
{
  "user": {
    "id": 1,
    "uuid": "...",
    "email": "joao@example.com",
    "phone": "11999999999",
    "state": "SP",
    "city": "São Paulo",
    "school": "Escola Central",
    "class": 1,
    "shift": 1
  }
}
```

Note: `password` and `remember_token` are hidden in model serialization.

Common errors:

- `401` unauthenticated
- `404` UUID not found for authenticated user

### `PATCH /api/profile`

Updates authenticated user's profile.

Auth:

- requires `X-API-KEY`
- requires `Authorization: Bearer {token}`

Request body:

```json
{
  "phone": "11999999999",
  "email": "joao.novo@example.com",
  "state": "SP",
  "city": "São Paulo",
  "school": "Escola Central",
  "class": 1,
  "shift": 1
}
```

Validations:

- `phone`: required, string, max:20
- `email`: required, email, max:255, unique (ignores current user)
- `state`: required, string, max:100
- `city`: required, string, max:100
- `school`: required, string, max:255
- `class`: required, integer, exists:classes,id
- `shift`: required, integer, exists:shifts,id

Success (`200`):

```json
{
  "message": "Perfil atualizado com sucesso.",
  "user": {
    "id": 1,
    "email": "joao.novo@example.com",
    "phone": "11999999999",
    "state": "SP",
    "city": "São Paulo",
    "school": "Escola Central",
    "class": 1,
    "shift": 1
  }
}
```

Common errors:

- `401` unauthenticated
- `422` missing or invalid required fields

---

## Helper Data

### `GET /api/classes`

Returns school classes.

Success (`200`):

```json
{
  "classes": [
    { "id": 1, "name": "6º série" }
  ]
}
```

### `GET /api/shifts`

Returns shifts.

Success (`200`):

```json
{
  "shifts": [
    { "id": 1, "name": "Manhã" }
  ]
}
```

### `GET /api/difficulties`

Returns difficulty levels.

Success (`200`):

```json
{
  "difficulties": [
    { "id": 1, "name": "Fácil" },
    { "id": 2, "name": "Médio" },
    { "id": 3, "name": "Difícil" }
  ]
}
```

---

## Questions

### `GET /api/questions`

Returns questions with eager-loaded difficulty and class relationships.

Optional query params:

- `class_id` (integer, exists)
- `difficulty_id` (integer, exists)
- `quantity` (integer, min:1, max:100)

Behavior:

- always applies random ordering (`inRandomOrder()`)
- if `quantity` is sent, response is limited

Examples:

- `/api/questions`
- `/api/questions?class_id=1&difficulty_id=1`
- `/api/questions?class_id=1&difficulty_id=1&quantity=10`

Success (`200`):

```json
{
  "questions": [
    {
      "id": 1,
      "statement": "...",
      "alternative_a": "...",
      "alternative_b": "...",
      "alternative_c": "...",
      "alternative_d": "...",
      "correct_alternative": "a",
      "tip": "...",
      "difficulty_id": 1,
      "class_id": 1,
      "difficulty": {
        "id": 1,
        "name": "Fácil"
      },
      "school_class": {
        "id": 1,
        "name": "6º série"
      }
    }
  ]
}
```

Common errors:

- `422` for non-existing `class_id`/`difficulty_id` and out-of-range `quantity`

---

## Streak

Tracks consecutive days where the user completed at least one lesson.

### `GET /api/users/{uuid}/streak`

Returns streak summary for the user.

Success (`200`):

```json
{
  "user_uuid": "user-uuid",
  "current_streak": 4,
  "best_streak": 7,
  "last_lesson_date": "2026-03-04",
  "lesson_done_today": true
}
```

### `GET /api/users/{uuid}/streak/check-today`

Checks whether the user has already completed a lesson today.

Success (`200`):

```json
{
  "user_uuid": "user-uuid",
  "date": "2026-03-04",
  "lesson_done_today": true,
  "last_lesson_date": "2026-03-04"
}
```

### `PATCH /api/users/{uuid}/streak/complete-today`

Registers today's lesson completion for streak calculation.

Rules:

- if user already completed today, streak is not incremented (idempotent);
- if last completion was yesterday, `current_streak` is incremented;
- otherwise, `current_streak` is reset to `1`;
- `best_streak` is updated when `current_streak` exceeds it.

Success (`200`):

```json
{
  "message": "Lição do dia registrada com sucesso.",
  "user_uuid": "user-uuid",
  "current_streak": 4,
  "best_streak": 7,
  "last_lesson_date": "2026-03-04",
  "lesson_done_today": true
}
```

Same-day repeated call (`200`):

```json
{
  "message": "Lição de hoje já registrada.",
  "user_uuid": "user-uuid",
  "current_streak": 4,
  "best_streak": 7,
  "last_lesson_date": "2026-03-04",
  "lesson_done_today": true
}
```

Common errors:

- `401` invalid API key
- `404` user UUID not found

---

## Standard Errors and Responses

### `401 Unauthorized`

- Invalid API key:

```json
{ "message": "API key inválida." }
```

- Missing/invalid Bearer token on protected route:

```json
{ "message": "Não autenticado." }
```

### `422 Unprocessable Entity`

Laravel standard validation response:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["error message"]
  }
}
```

`LoginRequest` has custom validation message:

```json
{
  "message": "Os dados informados são inválidos.",
  "errors": {
    "email": ["O e-mail é obrigatório."],
    "password": ["A senha é obrigatória."]
  }
}
```

### `404 Not Found`

Can happen on `firstOrFail()` lookups (for example: UUID not found).

## Tests

Main API contracts are covered in:

- `tests/Feature/Api/AuthApiTest.php`
- `tests/Feature/Api/UserStreakApiTest.php`

This file validates:

- required API key
- registration/login
- forgot/reset password
- authenticated profile fetch and update
- classes/shifts/difficulties listing
- question listing and filters
- expected error responses (`401` and `422`)

`UserStreakApiTest.php` validates:

- streak summary retrieval
- idempotent same-day completion
- consecutive-day increment
- today completion check

Run all tests:

```bash
php artisan test --compact
```

Run only this suite:

```bash
php artisan test --compact tests/Feature/Api/AuthApiTest.php
```

## Useful Commands

```bash
# run full development environment
composer run dev

# run migrations
php artisan migrate

# clear config cache
php artisan config:clear

# format PHP code
vendor/bin/pint --format agent
```

## Final Notes

- All API responses are JSON.
- For frontend/mobile consumption, always send `X-API-KEY`.
- For protected routes, send both `X-API-KEY` and `Bearer token`.
