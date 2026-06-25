# Running The Exam Portal Backend Locally

This guide explains how to start the Laravel 13 backend on your local machine.

## Current Repository Status

This repo contains the application source, routes, migrations, models, controllers, services, seeders, tests, and documentation. It does not include Composer's generated `vendor` directory or a generated Laravel `.env` file.

The current Codex shell does not expose `php`, `composer`, or `laravel`, so local execution could not be verified here. The steps below are the exact path to run it once those tools are installed.

## Prerequisites

- PHP 8.3 or newer
- Composer
- PostgreSQL 16 or compatible local PostgreSQL
- Redis or Valkey if you want queues/cache/session behavior locally
- Make

Optional:

- Laravel installer
- Docker Desktop, if you prefer running PostgreSQL/Redis in containers

## First-Time Setup

From the repository root:

```bash
cd "/Users/abhaysingh/Desktop/exam portal"
make doctor
make setup
```

What `make setup` does:

- Creates `.env` from `.env.example` if missing.
- Installs Composer dependencies.
- Generates the Laravel app key.
- Runs database migrations.
- Seeds local demo data.

## Configure Local Database

The default `.env.example` expects PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=exam_portal
DB_USERNAME=postgres
DB_PASSWORD=
```

Before running migrations, create the local database:

```bash
createdb exam_portal
```

If your PostgreSQL username/password is different, update `.env` before running:

```bash
make migrate
```

## Start The API

```bash
make serve
```

Default local URL:

```text
http://127.0.0.1:8000
```

API prefix:

```text
http://127.0.0.1:8000/api/v1
```

Use a different host or port:

```bash
make serve HOST=0.0.0.0 PORT=8080
```

## Useful Make Commands

```bash
make help
make doctor
make install
make env
make key
make migrate
make seed
make fresh
make serve
make test
make lint
make queue
make schedule
make routes
make clear
```

## Demo Seed Data

The local demo seeder creates:

- Admin user
- Examiner user
- Student user
- One live demo exam
- One MCQ question
- One NAT question
- Student registration for the demo exam

The seeder is guarded so it refuses to run in production.

## Run Tests

```bash
make test
```

This runs `vendor/bin/phpunit`.

Current test files:

- `tests/Feature/ExamLifecycleTest.php`
- `tests/Feature/SecurityExposureTest.php`

Important: this is not full scenario coverage yet. See:

- `docs/SECURITY_AND_SCENARIO_AUDIT.md`
- `docs/AWS_DEPLOYMENT_AND_TEST_COVERAGE.md`

## Run Code Style Check

```bash
make lint
```

## Queue Worker

For future queued jobs:

```bash
make queue
```

## Scheduler

For scheduled tasks:

```bash
make schedule
```

In production this should run every minute through a process manager, cron, or ECS scheduled task.

## Common Problems

### `php: command not found`

Install PHP 8.3+ and make sure it is available in your shell path.

### `composer: command not found`

Install Composer and make sure it is available in your shell path.

### Database connection failed

Check:

- PostgreSQL is running.
- Database `exam_portal` exists.
- `.env` database username/password are correct.
- PostgreSQL accepts local connections on port `5432`.

### `vendor/bin/pint: No such file or directory`

Run:

```bash
make install
```

## Fresh Local Reset

This drops and recreates all tables, then runs seeders:

```bash
make fresh
```

Only use this for local development.

## Next Local Development Tasks

- Install PHP/Composer on the machine.
- Run `make setup`.
- Run `make test`.
- Add the remaining scenario tests listed in `docs/SECURITY_AND_SCENARIO_AUDIT.md`.
- Add Docker Compose for PostgreSQL and Redis to make setup easier.
