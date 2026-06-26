# Exam Portal Features And Build Checklist

Source reviewed: `/Users/abhaysingh/Downloads/ExamFlow_Documentation.txt`  
Review date: 2026-06-25  
Backend target confirmed: Laravel `13.x` official documentation at <https://laravel.com/docs/13.x>

## Product Feature Map

### Roles

- [x] Student role identified: register for exams, start sessions, save answers, submit, view own results.
- [x] Examiner role identified: create exams, manage questions, monitor sessions, grade descriptive answers, generate reports.
- [x] Admin role identified: all examiner permissions plus user management, publish/delete exams, release results, platform metrics.
- [x] Laravel role middleware drafted.
- [ ] Replace simple role column with permissions package if the product needs custom institution-level permissions.

### Authentication And Security

- [x] API token authentication planned with Laravel Sanctum.
- [x] Register, login, logout, and current-user endpoints drafted.
- [x] Password hashing via Laravel `Hash`.
- [x] Role-based route protection drafted.
- [ ] Add refresh-token rotation if the frontend requires long-lived sessions.
- [ ] Add email verification and password reset.
- [ ] Add request throttling profiles for normal APIs vs active exam APIs.
- [ ] Add immutable audit log table and event listeners.

### Exam Management

- [x] Exam lifecycle captured: draft, scheduled, live, completed, archived.
- [x] Exam CRUD endpoints drafted for examiner/admin.
- [x] Admin can edit/delete any non-live test.
- [x] Examiner can edit/publish/toggle instant results only for tests they created.
- [x] Admin-only result release routes drafted.
- [x] Test groups added so tests and students can be assigned together.
- [x] Registration and withdrawal endpoints drafted.
- [ ] Add section CRUD endpoints.
- [ ] Add question CRUD and bulk CSV/JSON import.
- [ ] Add question bank module.
- [ ] Add exam instructions/version locking after publish.

### Exam Session Engine

- [x] Session start endpoint drafted.
- [x] Registration and live-window validation planned.
- [x] Answer save endpoint drafted.
- [x] Submit endpoint drafted.
- [x] Summary endpoint drafted.
- [x] Timer service drafted.
- [ ] Store randomized per-candidate question order.
- [ ] Move answer autosave hot path to Redis.
- [ ] Add queued persistence job for high-concurrency answer writes.
- [ ] Add automatic force-submit scheduled command.
- [ ] Add Laravel Reverb WebSocket timer sync.

### Question Types

- [x] MCQ data model planned.
- [x] Multi-correct data model planned.
- [x] NAT answer model planned.
- [x] Descriptive answer path planned.
- [ ] Add file/media attachments for questions and options.
- [ ] Add rubrics for descriptive grading.
- [ ] Add partial marking for multi-correct questions.

### Grading And Results

- [x] Grading service drafted for MCQ, multi-correct, NAT, and descriptive pending state.
- [x] Result model and migration drafted.
- [x] Manual grading queue endpoint drafted.
- [x] Manual grading submission endpoint drafted.
- [ ] Add percentile and rank recomputation job.
- [ ] Add dispute workflow.
- [ ] Add section-level score breakdown table.
- [ ] Add result release visibility rules.

### Proctoring

- [x] Proctoring log model and endpoint drafted.
- [x] Flag listing and action endpoint drafted.
- [ ] Add screenshot upload to S3.
- [ ] Add client-side proctoring frontend hooks.
- [ ] Add face detection integration.
- [ ] Add auto-disqualification policy configuration.

### Analytics And Reports

- [x] Exam overview endpoint drafted.
- [x] Report generation endpoint stub drafted.
- [ ] Add score distribution endpoint.
- [ ] Add item analysis endpoint.
- [ ] Add time analysis endpoint.
- [ ] Add queued PDF/CSV report generation.
- [ ] Add S3 signed report downloads.

### Infrastructure

- [x] Laravel 13 backend approach selected.
- [x] PostgreSQL-first schema drafted in migrations.
- [x] Redis planned for cache, sessions, queues, timers, and autosave.
- [ ] Install PHP 8.3+, Composer, and Laravel dependencies.
- [ ] Run migrations and seeders.
- [ ] Add Docker Compose for PostgreSQL and Redis.
- [ ] Add GitHub Actions for tests, Pint, and security checks.
- [ ] Add Dockerfile and Kubernetes manifests.

## Laravel Backend Translation

The original documentation describes Node.js microservices. For Laravel 13, the recommended first build is a modular monolith:

- HTTP API: Laravel routing, controllers, form requests, API resources.
- Authentication: Laravel Sanctum.
- Authorization: role middleware first, policies later.
- Database: PostgreSQL with Eloquent models and migrations.
- Queue: Redis queue with Laravel jobs.
- Realtime: Laravel Reverb for WebSockets.
- Scaling: Laravel Octane plus horizontal app replicas when load testing proves the need.
- Observability: structured logs, Laravel Pulse/Telescope in non-production, OpenTelemetry later.

This keeps the first implementation understandable while preserving clean boundaries for future service extraction.

## Recommended Extra Features To Add

- [ ] Institution/tenant model for universities, coaching centers, and companies.
- [ ] Exam blueprint builder with sections, difficulty distribution, topic weights, and auto-paper generation.
- [ ] Candidate identity verification before session start.
- [ ] Accessibility mode with high contrast, keyboard navigation, and screen-reader-friendly exam UI.
- [ ] Graceful reconnect flow with offline answer buffer.
- [ ] Question versioning so published exams are immutable.
- [ ] Seat/admit card generation.
- [ ] Candidate accommodation settings such as extra time.
- [ ] Audit timeline per candidate and per exam.
- [ ] Bulk user import with validation preview.
- [ ] Webhook callbacks for LMS or HR systems.
- [ ] Plagiarism/similarity detection for descriptive answers.

## Build Steps

### Done In This Session

- [x] Read the provided ExamFlow documentation.
- [x] Confirmed Laravel 13 documentation exists on the official Laravel docs site.
- [x] Created `/Users/abhaysingh/Desktop/exam portal` folder.
- [x] Drafted Laravel 13-ready repository files in the workspace.
- [x] Added feature checklist and continuation ledger.
- [x] Added API routes matching the documentation's main flows.
- [x] Added core models, migrations, controllers, services, middleware, seeder, and feature tests.
- [x] Added `/api/v1/exam-groups` endpoints.
- [x] Added group-to-test and group-to-student assignment endpoints.
- [x] Added automatic student registration when a student is added to a group with tests.
- [x] Added automatic student registration when a test is added to a group with students.
- [x] Added ownership guards so examiners cannot edit another examiner's test.
- [x] Verified backend with 27 PHPUnit tests and 92 assertions.
- [x] Added current student session status to student exam-list responses.
- [x] Added `/api/v1/exams/{exam}/questions` for adding MCQ/multi-correct questions with options to draft tests.
- [x] Added `/api/v1/exams/{exam}/submissions` for examiner/admin submitted-answer review.

## Admin, Examiner, And Group Workflow Memory

- [x] Admin can create tests through `POST /api/v1/exams`.
- [x] Admin can edit any draft test through `PUT /api/v1/exams/{exam}`.
- [x] Admin can delete any non-live test through `DELETE /api/v1/exams/{exam}`.
- [x] Examiner can create tests through `POST /api/v1/exams`.
- [x] Examiner can edit, publish, and toggle instant results only when `exams.created_by` matches the examiner user id.
- [x] Admin/examiner can create a group through `POST /api/v1/exam-groups`.
- [x] Admin can manage any group; examiner can manage only groups they created.
- [x] Admin/examiner can attach an allowed test to a group through `POST /api/v1/exam-groups/{group}/exams`.
- [x] Admin/examiner can attach a student to a group through `POST /api/v1/exam-groups/{group}/students`.
- [x] Adding a student to a group registers that student for every current group test.
- [x] Adding a test to a group registers every current group student for that test.
- [x] Student exam list exposes only the current student's own session status, so submitted attempts render as closed without exposing other candidates.
- [x] Examiner/admin submitted-answer review loads submitted sessions with student identity, result, answers, question text, and selected option ids.
- [x] Question add flow is draft-only so live tests are not mutated after students can start them.
- [ ] Add audit logs for group membership, test edits, and deletes before production.
- [ ] Add richer question editing endpoints for full test editing beyond the current title/duration/starter-question flow.

### Remaining Before First Local Run

- [ ] Install PHP 8.3 or newer.
- [ ] Install Composer.
- [ ] Run `composer install` inside the repository.
- [ ] Generate app key with `php artisan key:generate`.
- [ ] Configure PostgreSQL database in `.env`.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan db:seed --class=DemoSeeder`.
- [x] Run `vendor/bin/phpunit`.
- [ ] Start API with `php artisan serve`.

### Remaining Product Work

- [ ] Flesh out request validation classes for every endpoint.
- [ ] Add API resources for consistent response shapes.
- [ ] Add policies for exam ownership and result visibility.
- [ ] Add Redis answer buffering.
- [ ] Add queue jobs for grading, reports, notifications, and analytics.
- [ ] Add Reverb events for timer sync and live monitoring.
- [ ] Add S3 upload/signing for question media, proctoring shots, and reports.
- [ ] Add full test coverage for exam lifecycle, RBAC, grading, and proctoring.

## Continuation Notes For Future Sessions

- Do not restart from the Node.js stack in the original documentation; the user explicitly asked for Laravel 13 backend.
- Treat this repository as a Laravel API skeleton until Composer can install the real framework files.
- Keep this checklist updated whenever code is added.
- Prefer implementing the session engine next: section/question CRUD, randomized question order, answer autosave, submission, and grading tests.
