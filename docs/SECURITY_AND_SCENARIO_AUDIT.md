# Security And Scenario Audit

Date: 2026-06-25  
Scope: Laravel 13 exam portal backend scaffold  
Method: Static code review, route/controller review, secret-pattern scan, and scenario checklist comparison.

## Executive Summary

- [x] No real AWS keys, private keys, GitHub tokens, OpenAI-style API keys, `.env` file, PEM files, or credential files were found in the repository scan.
- [x] Public registration privilege escalation was found and fixed.
- [x] Answer-key exposure through serialized options was found and fixed.
- [x] NAT correct answer serialization was hidden.
- [x] Question explanation serialization was hidden to avoid leaking answer rationale during exams.
- [x] Cross-exam answer submission validation was added.
- [x] Demo seeder now refuses to run in production.
- [x] Demo seeder no longer prints a password to command output.
- [ ] PHPUnit tests could not be executed in this shell because `php` and `composer` are unavailable.
- [ ] Scenario coverage is not complete yet; the repository has starter tests plus new security tests, but many scenarios from the deployment checklist still need automated tests.

## Commands Attempted

```bash
which php
which composer
rg -n "password|secret|token|key|AWS_ACCESS|AWS_SECRET|APP_KEY|DB_PASSWORD|PRIVATE|BEGIN|AKIA|AIza|sk-|xoxb|ghp_|github_pat|client_secret" .
find . -maxdepth 3 -type f -name '.env' -o -name '*.pem' -o -name '*.key' -o -name '*secret*' -o -name '*credential*'
rg -n "is_correct|correct_value|tolerance|role' => \\['required'|Rule::in\\(\\['student', 'examiner', 'admin'\\]\\)" app tests routes database docs
```

Result:

- `php`: not found.
- `composer`: not found.
- No `.env`, private key, PEM, or credential files found.
- Only demo/test passwords were found after patching; they are guarded for local development and are not production secrets.

## Security Issues Fixed

### 1. Public Role Self-Assignment

Risk: public `/api/v1/auth/register` accepted `student`, `examiner`, or `admin`, allowing a user to self-register as privileged.

Fix:

- Public registration now accepts only `student`.
- The backend forces `role = student` regardless of request body.
- Admin/examiner creation remains available through admin-only user management routes.

Files:

- `app/Http/Controllers/Api/AuthController.php`
- `tests/Feature/SecurityExposureTest.php`

### 2. Correct Option Exposure

Risk: `GET /api/v1/exams/{exam}` and session-start responses loaded `questions.options`. The `options` model included `is_correct`, which could leak the answer key to students.

Fix:

- `Option::$hidden = ['is_correct']`.
- Security test added to assert exam details do not expose `is_correct`.

Files:

- `app/Models/Option.php`
- `tests/Feature/SecurityExposureTest.php`

### 3. NAT Answer Exposure

Risk: if NAT answers are loaded in future responses, `correct_value` and `tolerance` could leak.

Fix:

- `NatAnswer::$hidden = ['correct_value', 'tolerance']`.

Files:

- `app/Models/NatAnswer.php`

### 4. Explanation Exposure

Risk: question explanations often reveal the solution and should not be visible during exam-taking.

Fix:

- `Question::$hidden = ['explanation']`.

Files:

- `app/Models/Question.php`

### 5. Cross-Exam Answer Injection

Risk: answer submission only validated that `question_id` existed. A malicious client could submit a question from another exam/session.

Fix:

- Answer endpoint now verifies the question belongs to the session's exam.
- Selected option IDs must belong to the submitted question.
- Security test added for rejecting a question from another exam.

Files:

- `app/Http/Controllers/Api/SessionController.php`
- `tests/Feature/SecurityExposureTest.php`

### 6. Demo Credential Exposure

Risk: demo seeder printed the local demo password in command output and could run in production.

Fix:

- Demo seeder aborts in production.
- Demo seeder no longer prints the demo password.

Files:

- `database/seeders/DemoSeeder.php`

## Current Automated Test Files

- `tests/Feature/ExamLifecycleTest.php`
  - Student can register for an exam.
  - Unregistered student cannot start a session.
  - Registered student can start a live session.

- `tests/Feature/SecurityExposureTest.php`
  - Public registration cannot self-assign admin role.
  - Exam details do not expose correct options.
  - Answer submission cannot reference a question from another exam.

## Scenario Coverage Status

### Covered By Current Tests

- [x] Student registration for exam.
- [x] Unregistered student blocked from session start.
- [x] Registered student can start live session.
- [x] Public registration cannot self-assign admin role.
- [x] Exam details should not expose `is_correct`.
- [x] Answer endpoint should reject questions outside the session exam.

### Implemented In Code But Not Fully Tested

- [ ] Register/login/logout/me endpoints.
- [ ] Role middleware.
- [ ] Exam create/update/delete/publish routes.
- [ ] Exam registration withdrawal.
- [ ] My registrations endpoint.
- [ ] Session show/answer/submit/summary endpoints.
- [ ] MCQ, multi-correct, NAT, and descriptive grading logic.
- [ ] Manual grading queue and grading submission.
- [ ] Proctoring flag store/list/action.
- [ ] Analytics overview.
- [ ] Report generation stub.
- [ ] Admin user management.

### Not Yet Implemented Or Not Production-Ready

- [ ] Dedicated result view endpoints for students.
- [ ] Section CRUD.
- [ ] Question CRUD.
- [ ] Question bank.
- [ ] Bulk question import.
- [ ] Redis answer buffering.
- [ ] Queue-backed persistence and grading jobs.
- [ ] WebSocket timer sync.
- [ ] Force-submit scheduler.
- [ ] S3 upload handling for screenshots/question media/reports.
- [ ] Signed URL download controls.
- [ ] Full audit log.
- [ ] Password reset and email verification.
- [ ] Rate limits by endpoint category.
- [ ] Production Dockerfile and AWS deploy workflow.

## Sensitive Information Status

### No Sensitive Values Found

- [x] No `.env` file committed.
- [x] No real `APP_KEY`.
- [x] No real `DB_PASSWORD`.
- [x] No AWS access key ID or secret.
- [x] No private key blocks.
- [x] No GitHub token patterns.
- [x] No OpenAI-style `sk-` key patterns.

### Non-Secret Demo/Test Values Found

- `password123` appears in tests/factory/demo seeder.
- These are local test/demo values, not production secrets.
- Demo seeder now aborts in production.

## Remaining Security Recommendations

- [ ] Add Laravel rate limiting for auth, exam start, answer save, and proctoring endpoints.
- [ ] Add policies for exam ownership, result visibility, and grading authority.
- [ ] Split student exam payloads from examiner/admin payloads with API Resources.
- [ ] Add result endpoints that enforce ownership and release policy.
- [ ] Add request IDs and structured error responses.
- [ ] Add audit logs for auth, exam changes, session starts, submissions, grading, and admin actions.
- [ ] Add `APP_DEBUG=false` check in production container startup.
- [ ] Add CI secret scanning with Gitleaks or TruffleHog.
- [ ] Add dependency scanning in CI.
- [ ] Add S3 bucket policies with Block Public Access and signed URL-only access.
- [ ] Use AWS Secrets Manager or SSM Parameter Store for production secrets.

## Required Next Test Work

Create these test files next:

- `tests/Feature/AuthTest.php`
- `tests/Feature/RbacTest.php`
- `tests/Feature/ExamManagementTest.php`
- `tests/Feature/RegistrationTest.php`
- `tests/Feature/SessionLifecycleTest.php`
- `tests/Feature/AnswerSavingTest.php`
- `tests/Feature/GradingTest.php`
- `tests/Feature/ProctoringTest.php`
- `tests/Feature/ResultsVisibilityTest.php`
- `tests/Feature/ReportsTest.php`
- `tests/Feature/AnalyticsTest.php`

Run after PHP/Composer are available:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
vendor/bin/pint --test
```

## Final Audit Judgment

The repository is **safer after this audit**, and the highest-risk scaffold leaks found in static review were patched. However, it is **not yet fully scenario-covered** and should not be treated as production-ready until PHP/Composer are installed, dependencies are resolved, the expanded feature tests are implemented, and the suite passes in CI.
