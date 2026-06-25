# Backend Review Checklist

Review date: 2026-06-25  
Repo: `exam-portal-backend`  
Scope: Laravel API auth, exam discovery, session start, answer save, submit, proctoring, grading, and security exposure tests.

## Critical Flow Findings

- [x] Fixed: student exam detail responses no longer expose question content before session start. See `app/Http/Controllers/Api/ExamController.php`.
- [x] Fixed: only the session owner can save answers or submit a session. Examiner/admin users can still inspect through read endpoints but cannot mutate a candidate attempt.
- [x] Fixed: submitted, disqualified, or expired sessions reject answer saves.
- [x] Fixed: resumed sessions return questions using the stored `question_order`.
- [x] Added regression tests for pre-session question exposure, cross-exam answer injection, examiner mutation attempts, and expired answer saves.

## Security Checklist

- [x] Public registration cannot self-assign admin/examiner roles.
- [x] User password and remember token are hidden from JSON responses.
- [x] Option `is_correct` and NAT `correct_value`/`tolerance` are hidden by model serialization.
- [x] Students cannot answer questions from another exam.
- [x] Students cannot save answers after session expiry.
- [x] Non-owner users cannot save or submit a student session.
- [ ] Move browser auth from bearer tokens in localStorage to secure HTTP-only cookies before production.
- [ ] Add rate limiting to login, register, answer save, and proctoring flag endpoints.
- [ ] Add audit logging for admin user changes, exam publish/release actions, grading actions, and disqualification actions.
- [ ] Add explicit authorization policies instead of controller-local role checks as the app grows.

## Functional Checklist

- [x] Student can register for an exam.
- [x] Unregistered student cannot start a live session.
- [x] Registered student can start a live session.
- [x] Session answer save validates that option IDs belong to the requested question.
- [x] Session submit grades through `GradingService`.
- [ ] Add tests for MCQ correct/wrong scoring.
- [ ] Add tests for multi-correct scoring.
- [ ] Add tests for NAT tolerance scoring.
- [ ] Add tests for descriptive manual grading and result recompute.
- [ ] Add tests for result release visibility.
- [ ] Add tests for proctoring flag action workflow.
- [ ] Implement scheduled start/end-time enforcement, not only `status === live`.
- [ ] Decide whether students may register for draft/completed exams; currently capacity is checked but lifecycle status is not.

## API Contract Checklist

- [x] `/api/v1/auth/login` returns bearer token and user.
- [x] `/api/v1/exams` returns paginated exam list.
- [x] `/api/v1/sessions/start` returns session id, ordered questions, sections, timer, and server time.
- [x] `/api/v1/sessions/{session}/answer` accepts MCQ, multi-correct, NAT, and descriptive answer payloads.
- [ ] Add dedicated response resources/DTOs so API output does not depend on raw Eloquent serialization.
- [ ] Add OpenAPI documentation or a checked-in API contract for frontend/backend alignment.
- [ ] Standardize error response bodies for `403`, `409`, and `422` cases.

## Verification Commands

```bash
vendor/bin/phpunit
vendor/bin/pint --test app/Http/Controllers/Api/ExamController.php app/Http/Controllers/Api/SessionController.php app/Services/ExamTimerService.php tests/Feature/SecurityExposureTest.php
```

## Next Recommended Work

- [ ] Add Laravel policies for `Exam`, `ExamSession`, `Answer`, and `ProctoringLog`.
- [ ] Add server-side autosubmit/timeout handling for sessions that reach zero.
- [ ] Add CI checks for PHPUnit, secret scanning, and dependency audit.
- [ ] Add production database config and AWS deployment smoke tests.
