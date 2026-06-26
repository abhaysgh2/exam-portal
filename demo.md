# Backend Local Demo Walkthrough

Use this when you want to reset the local backend and test the full API flow.

## Start Fresh

```bash
cd "/Users/abhaysingh/Desktop/exam portal"
make setup-sqlite
make serve
```

Backend URL:

```text
http://127.0.0.1:8000
```

API base:

```text
http://127.0.0.1:8000/api/v1
```

## Demo Accounts

```text
student@example.com / password123
examiner@example.com / password123
admin@example.com / password123
```

The seeded exam id is generated locally. Get it with the login/list commands below.

## Scenario 1: Student Login And Exam List

```bash
STUDENT_TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"student@example.com","password":"password123"}' | jq -r .access_token)

curl -s http://127.0.0.1:8000/api/v1/exams \
  -H "Authorization: Bearer $STUDENT_TOKEN" | jq .

EXAM_ID=$(curl -s http://127.0.0.1:8000/api/v1/exams \
  -H "Authorization: Bearer $STUDENT_TOKEN" | jq -r '.data[0].id')
```

## Scenario 2: Start Session, Answer, Submit, Instant Result

```bash
SESSION_JSON=$(curl -s -X POST http://127.0.0.1:8000/api/v1/sessions/start \
  -H "Authorization: Bearer $STUDENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"exam_id\":\"$EXAM_ID\"}")

SESSION_ID=$(echo "$SESSION_JSON" | jq -r .session_id)
MCQ_ID=$(echo "$SESSION_JSON" | jq -r '.questions[] | select(.type=="mcq") | .id')
MCQ_OPTION_ID=$(echo "$SESSION_JSON" | jq -r '.questions[] | select(.type=="mcq") | .options[] | select(.text=="4") | .id')
NAT_ID=$(echo "$SESSION_JSON" | jq -r '.questions[] | select(.type=="nat") | .id')

curl -s -X PATCH "http://127.0.0.1:8000/api/v1/sessions/$SESSION_ID/answer" \
  -H "Authorization: Bearer $STUDENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"question_id\":\"$MCQ_ID\",\"selected_option_id\":\"$MCQ_OPTION_ID\",\"visited\":true}" | jq .

curl -s -X PATCH "http://127.0.0.1:8000/api/v1/sessions/$SESSION_ID/answer" \
  -H "Authorization: Bearer $STUDENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"question_id\":\"$NAT_ID\",\"nat_value\":9,\"visited\":true}" | jq .

curl -s -X POST "http://127.0.0.1:8000/api/v1/sessions/$SESSION_ID/submit" \
  -H "Authorization: Bearer $STUDENT_TOKEN" | jq .
```

Expected: `result_visible` is `true`, final score is `10.00`, and message is `Your result is ready.`

To retest this same scenario, run `make fresh` or `make setup-sqlite` because one student can have only one session per exam.

If the browser shows `Exam has ended`, refresh the local demo window and clear the old student attempt:

```bash
cd "/Users/abhaysingh/Desktop/exam portal"
make fresh
```

`make fresh` reseeds `Demo Engineering Aptitude Exam` as live for the next hour.

## Scenario 3: Examiner Instant Result Toggle

```bash
EXAMINER_TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"examiner@example.com","password":"password123"}' | jq -r .access_token)

curl -s -X PATCH "http://127.0.0.1:8000/api/v1/exams/$EXAM_ID/instant-results" \
  -H "Authorization: Bearer $EXAMINER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"enabled":false}' | jq .

curl -s -X PATCH "http://127.0.0.1:8000/api/v1/exams/$EXAM_ID/instant-results" \
  -H "Authorization: Bearer $EXAMINER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"enabled":true}' | jq .
```

Expected: disabling returns `Instant results disabled.` and enabling returns `Instant results enabled.`

## Scenario 4: Examiner Creates And Publishes A Basic Test

The frontend now exposes this through `Exams -> Create test`. The matching API flow is:

```bash
NEW_EXAM_ID=$(curl -s -X POST http://127.0.0.1:8000/api/v1/exams \
  -H "Authorization: Bearer $EXAMINER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title":"Examiner Posted MCQ Test",
    "duration_minutes":30,
    "total_marks":1,
    "pass_marks":1,
    "status":"draft",
    "show_results_after":"submit",
    "starter_question":{
      "text":"What is 2 + 2?",
      "marks":1,
      "negative_marks":0,
      "options":[
        {"text":"3","is_correct":false},
        {"text":"4","is_correct":true}
      ]
    }
  }' | jq -r .id)

curl -s -X POST "http://127.0.0.1:8000/api/v1/exams/$NEW_EXAM_ID/publish" \
  -H "Authorization: Bearer $EXAMINER_TOKEN" | jq .
```

Expected: the created test has one MCQ answer key and publish changes the test to `scheduled`.

## Scenario 5: Security Checks

```bash
vendor/bin/phpunit
vendor/bin/pint --test
```

Expected: all tests pass, no formatting issues.
