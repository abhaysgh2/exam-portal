# Exam Portal Backend

Laravel 13 API backend for an enterprise online exam portal based on the provided ExamFlow documentation.

## Current Status

- Documentation and implementation checklist: `docs/FEATURES_AND_STEPS.md`
- Backend framework target: Laravel 13.x
- Local app status: runnable backend API with routes, migrations, models, controllers, middleware, seeders, tests, and local setup commands
- Verified locally with PHP 8.5.7, Composer 2.10.1, SQLite, and PHPUnit
- Current test status: `make test` passes with 15 tests and 39 assertions
- Frontend status: not built yet; this repository is backend/API only

## Quick Local Setup

From the project root:

```bash
cd "/Users/abhaysingh/Desktop/exam portal"
make setup-sqlite
make test
make serve
```

On a fresh machine where PHP, Composer, or Make may be missing:

```bash
cd "/Users/abhaysingh/Desktop/exam portal"
make bootstrap
make serve
```

`make bootstrap` installs supported system tools, configures SQLite, installs Composer dependencies, migrates/seeds the database, and runs tests.

For detailed local setup steps, see `docs/LOCAL_DEVELOPMENT.md`.

## Local URLs

- Backend status: `http://127.0.0.1:8000/`
- Health check: `http://127.0.0.1:8000/health`
- API base: `http://127.0.0.1:8000/api/v1`

## Demo Users

After seeding local demo data:

- `admin@example.com`
- `examiner@example.com`
- `student@example.com`

Demo password:

```text
password123
```

## Main API Areas

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/exams`
- `POST /api/v1/exams`
- `POST /api/v1/exams/{exam}/publish`
- `POST /api/v1/exams/{exam}/register`
- `POST /api/v1/sessions/start`
- `PATCH /api/v1/sessions/{session}/answer`
- `POST /api/v1/sessions/{session}/submit`
- `POST /api/v1/proctoring/flag`
- `GET /api/v1/grading/queue`
- `GET /api/v1/analytics/exams/{exam}/overview`

## Useful Commands

```bash
make help
make platform
make doctor
make setup-sqlite
make serve
make test
make routes
```

## Documentation

- Feature checklist: `docs/FEATURES_AND_STEPS.md`
- Local setup: `docs/LOCAL_DEVELOPMENT.md`
- AWS deployment plan: `docs/AWS_DEPLOYMENT_AND_TEST_COVERAGE.md`
- Security and scenario audit: `docs/SECURITY_AND_SCENARIO_AUDIT.md`
- Local demo walkthrough: `demo.md`

## Notes

This repository converts the original Node.js microservice plan into a Laravel modular monolith. The architecture keeps clear service boundaries so Redis queues, Laravel Reverb, Horizon, Octane, S3, and separate workers can be added without rewriting the domain model.
