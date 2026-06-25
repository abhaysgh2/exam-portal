# Exam Portal Backend

Laravel 13 API backend skeleton for an enterprise online exam portal based on the provided ExamFlow documentation.

## Current Status

- Documentation and implementation checklist: `docs/FEATURES_AND_STEPS.md`
- Backend framework target: Laravel 13.x
- Local scaffold status: source code, routes, migrations, models, controllers, middleware, seeders, and tests are drafted
- Dependency install status: pending because `php`, `composer`, and `laravel` are not available in the current shell

## Setup Once PHP And Composer Are Installed

```bash
make doctor
make setup
make serve
```

If you create a fresh Laravel app, copy the `app`, `routes`, `database`, `config`, `bootstrap`, `tests`, and `docs` folders from this repository into it.

For detailed local setup steps, see `docs/LOCAL_DEVELOPMENT.md`.

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

## Notes

This repository converts the original Node.js microservice plan into a Laravel modular monolith. The architecture keeps clear service boundaries so Redis queues, Laravel Reverb, Horizon, Octane, S3, and separate workers can be added without rewriting the domain model.
