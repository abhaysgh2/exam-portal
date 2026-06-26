# Backend Review Checklist

Review date: 2026-06-25  
Repo: `exam-portal-backend`  
Scope: Laravel API auth, exam discovery, session start, answer save, submit, proctoring, grading, and security exposure tests.

## Critical Flow Findings

- [x] Student exam attempts have a one-minute post-timer answer-save grace window.
- [x] Examiner/admin tokens include one-hour expiry and renewal support.
- [x] Examiner UI has create/publish API support for a basic MCQ test.
- [x] Demo seeder refreshes the demo exam live window so the local demo test does not stay expired.
- [x] Fixed: student exam detail responses no longer expose question content before session start. See `app/Http/Controllers/Api/ExamController.php`.
- [x] Fixed: only the session owner can save answers or submit a session. Examiner/admin users can still inspect through read endpoints but cannot mutate a candidate attempt.
- [x] Fixed: submitted, disqualified, or expired sessions reject answer saves.
- [x] Fixed: resumed sessions return questions using the stored `question_order`.
- [x] Fixed: final submit returns either a hidden-result submitted message or an instant-result payload based on the exam setting.
- [x] Added examiner/admin instant-result toggle endpoint with answer-key validation.
- [x] Added publishing guard so instant-result exams cannot publish until objective answer keys exist.
- [x] Fixed: `/health` now returns JSON without session/XSRF cookies for load balancer checks.
- [x] Added regression tests for pre-session question exposure, cross-exam answer injection, examiner mutation attempts, and expired answer saves.

## Security Checklist

- [x] Verify role-aware token expiry metadata is returned and frontend localStorage cleanup is implemented.
- [x] Public registration cannot self-assign admin/examiner roles.
- [x] User password and remember token are hidden from JSON responses.
- [x] Option `is_correct` and NAT `correct_value`/`tolerance` are hidden by model serialization.
- [x] Students cannot answer questions from another exam.
- [x] Students cannot save answers after session expiry.
- [x] Non-owner users cannot save or submit a student session.
- [x] Instant-result enablement rejects exams without answer keys using `please first upload the answers`.
- [x] Descriptive questions are blocked from instant-result enablement because they require manual grading.
- [x] Login, register, session start, answer save, and proctoring flag endpoints have starter throttles.
- [ ] Move browser auth from bearer tokens in localStorage to secure HTTP-only cookies before production.
- [x] Add rate limiting to login, register, answer save, and proctoring flag endpoints.
- [ ] Add audit logging for admin user changes, exam publish/release actions, grading actions, and disqualification actions.
- [ ] Add explicit authorization policies instead of controller-local role checks as the app grows.

## Functional Checklist

- [x] Add tests for token expiry metadata on login/register.
- [x] Add tests for examiner/admin token renewal and student renewal rejection.
- [x] Add tests for answer save during the one-minute post-timer grace period.
- [x] Add tests for answer rejection after the one-minute post-timer grace period.
- [x] Add API support/tests for examiner creating a basic test with question/options/answer key.
- [x] Add API support/tests for examiner publishing a test after answer keys exist.
- [x] Add test that reseeding refreshes the demo exam live window.
- [x] Student can register for an exam.
- [x] Unregistered student cannot start a live session.
- [x] Registered student can start a live session.
- [x] Session answer save validates that option IDs belong to the requested question.
- [x] Session submit grades through `GradingService`.
- [x] Session submit hides result when `show_results_after` is `manual_release`.
- [x] Session submit returns result details when `show_results_after` is `submit`.
- [x] Students cannot register for draft/completed/archived exams.
- [x] Live sessions cannot start before `start_time` or after `end_time`.
- [ ] Add tests for MCQ correct/wrong scoring.
- [ ] Add tests for multi-correct scoring.
- [ ] Add tests for NAT tolerance scoring.
- [ ] Add tests for descriptive manual grading and result recompute.
- [ ] Add tests for result release visibility.
- [ ] Add tests for proctoring flag action workflow.
- [x] Implement scheduled start/end-time enforcement, not only `status === live`.
- [x] Decide whether students may register for draft/completed exams; registration is restricted to scheduled/live exams.

## API Contract Checklist

- [x] `/api/v1/auth/login` returns bearer token and user.
- [x] `/api/v1/exams` returns paginated exam list.
- [x] `/api/v1/sessions/start` returns session id, ordered questions, sections, timer, and server time.
- [x] `/api/v1/sessions/{session}/answer` accepts MCQ, multi-correct, NAT, and descriptive answer payloads.
- [x] `/api/v1/sessions/{session}/submit` returns submit status, result visibility, user-facing message, and result data only when visible.
- [x] `/api/v1/exams/{exam}/instant-results` toggles instant result visibility for examiner/admin users.
- [x] `/health` returns JSON status and does not emit session cookies.
- [ ] Add dedicated response resources/DTOs so API output does not depend on raw Eloquent serialization.
- [ ] Add OpenAPI documentation or a checked-in API contract for frontend/backend alignment.
- [ ] Standardize error response bodies for `403`, `409`, and `422` cases.

## Verification Commands

```bash
vendor/bin/phpunit
vendor/bin/pint --test
```

## Live Smoke Test

- [x] `php artisan migrate:fresh --seed` rebuilt local SQLite demo data.
- [x] Laravel served on `http://127.0.0.1:18080`.
- [x] Student login, exam list, session start, MCQ save, NAT save, submit, and instant result response were verified with curl.
- [x] Examiner login and instant-results toggle were verified with curl.
- [x] `/health` returned JSON without `Set-Cookie`.

## Next Recommended Work

- [ ] Add Laravel policies for `Exam`, `ExamSession`, `Answer`, and `ProctoringLog`.
- [ ] Add server-side autosubmit/timeout handling for sessions that reach zero.
- [ ] Add CI checks for PHPUnit, secret scanning, and dependency audit.
- [ ] Add production database config and AWS deployment smoke tests.
