# SpeakLoud — Backend

[Laravel](https://laravel.com) API and web application for **SpeakLoud** ([speakloud.app](https://speakloud.app)): a language-exchange platform where learners find partners, publish practice slots, send claims, and chat after a match is accepted.

## Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 13, PHP 8.3+ |
| UI | Livewire 4, Volt (single-file components), Flux UI, Tailwind CSS v4 |
| Database | MySQL 8 |
| Cache / queues | Redis |
| Tests | Pest |
| Local runtime | Laravel Sail (Docker) |

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or compatible Docker engine)
- [Node.js](https://nodejs.org/) 20+ (for Vite on the host)
- Composer (used via Sail; host install optional)

## Quick start

All PHP, Artisan, and Composer commands run **inside Sail**. Do not run `php` or `composer` on the host unless you know the environment matches the container.

```bash
cd backend

# 1. Environment
cp .env.example .env
# Edit .env if needed (APP_PORT, DB_*, etc.)

# 2. Install PHP dependencies (first time)
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php83-composer:latest \
  composer install --ignore-platform-reqs

# 3. Start containers (app, MySQL, Redis)
./vendor/bin/sail up -d

# 4. Application key & database
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed

# 5. Frontend (separate terminal, on host)
npm install
npm run dev
```

Open the app at the URL in `.env` (default **`http://localhost:8100`** when `APP_PORT=8100`).

Optional Sail alias:

```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

## Demo data

After `migrate --seed`, the database includes ~100 demo users plus sample schedules, claims, conversations, blog posts, and FAQs.

| Role | Email | Password |
|------|--------|----------|
| Admin | `admin@speakloud.test` | `123456789` |
| Users | `user1@speakloud.test` … `user100@speakloud.test` | `123456789` |

Reset and reseed (local only — **wipes all data**):

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

Seeders: `LanguageSeeder`, `InterestSeeder`, `ContentSeeder`, `DemoDataSeeder` (see `database/seeders/`).

## Common commands

```bash
# Containers
./vendor/bin/sail up -d
./vendor/bin/sail down

# Database
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed

# Queue worker
./vendor/bin/sail artisan queue:work redis

# Tests
./vendor/bin/sail pest
./vendor/bin/sail pest --parallel
./vendor/bin/sail pest tests/Feature/SomeTest.php
./vendor/bin/sail pest --coverage --min=80   # requires PCOV

# Code style
./vendor/bin/sail pint

# Shell inside container
./vendor/bin/sail shell
```

Frontend hot reload runs on the host:

```bash
npm run dev    # Vite, typically :5173
npm run build  # production assets
```

## Architecture

```
HTTP / Volt page
 └── Action          (one use-case: SendClaim, AcceptClaim, …)
      ├── Service    (reusable logic)
      └── Repository (Eloquent, behind interfaces)
```

- **Actions** — `app/Actions/`, verb+noun naming.
- **Repositories** — `app/Repositories/` + `Contracts/I*Repository`; bound in `AppServiceProvider`.
- **UI** — Volt components in `resources/views/volt/` (not class-based Livewire in `app/Livewire/`).
- **Routes** — `routes/web.php`; full-page Volt routes via `Volt::route()`.

Domain overview: users publish **schedules** (recurring or one-off), others send **claims**, hosts accept → **conversation** + **messages** unlock.

Full database DDL and relationships: [`../project-files/SCHEMA.md`](../project-files/SCHEMA.md).

## Main routes (local)

| Path | Description | Auth |
|------|-------------|------|
| `/` | Landing | Guest |
| `/discover` | Partner grid | Yes |
| `/search` | Partner list search | Yes |
| `/schedule` | Your availability | Yes |
| `/claims` | Incoming / outgoing claims | Yes |
| `/messages` | Conversations | Yes |
| `/profile` | Profile overview | Yes |
| `/blog` | Blog + FAQ | Public |
| `/login`, `/register` | Auth | Guest |

## Project layout

```
app/
├── Actions/
├── Http/Controllers/
├── Models/
├── Repositories/
│   └── Contracts/
└── Providers/
database/
├── migrations/
├── seeders/
└── factories/
resources/
├── views/volt/      # Livewire Volt pages
├── views/components/
└── css/app.css      # Tailwind + Flux theme
routes/web.php
compose.yaml         # Sail services
```

## Database (Docker)

MySQL is exposed on the host via `FORWARD_DB_PORT` in `.env` (default **3309** → container 3306).

| Setting | Typical local value |
|---------|---------------------|
| Host (from Mac) | `127.0.0.1` |
| Port | `3309` |
| Database | `speakloud` |
| User / password | `sail` / `password` |

CLI:

```bash
./vendor/bin/sail mysql
```

## Testing

Feature tests use `RefreshDatabase` (see `tests/Pest.php`). Target **≥ 80%** coverage in CI.

```bash
./vendor/bin/sail pest
```

## Environment notes

- Copy `.env.example` to `.env` and set `APP_URL` / `APP_PORT` to match how you access the app.
- `DB_HOST=mysql` is correct **inside** Sail; use `127.0.0.1` and `FORWARD_DB_PORT` from GUI clients on the host.
- Session and queue drivers are configured for MySQL/Redis in the shipped `.env`.

## Further documentation

- Repo-wide dev guide: [`../CLAUDE.md`](../CLAUDE.md)
- Detailed setup: [`../project-files/SETUP.md`](../project-files/SETUP.md)

## License

MIT (Laravel application skeleton). SpeakLoud application code is part of the SpeakLoud project.
