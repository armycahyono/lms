---
name: cbt-assessment-patterns
description: "Design and implement reliable CBT exams, quizzes, question banks, autosave, timer sync, randomization, and grading workflows. Apply whenever working on CbtExam, CbtExamResult, Quiz, QuizAttempt, QuizQuestion, QuestionBank, QuestionStimulus, QuestionItemHistory, or RemedialEnrichment."
license: MIT
metadata:
  author: lms-project
---

# CBT & Assessment Patterns

Ensures correctness, concurrency safety, and data integrity for exams, quizzes, and question banks.

## When to apply

- Implementing or modifying `CbtExam`, `CbtExamResult`, `Quiz`, `QuizAttempt`, `QuizQuestion`, `QuestionBank`, `QuestionStimulus`, `QuestionItemHistory`, or `RemedialEnrichment`.
- Building exam-taking UIs, autosave handlers, timers, randomization, or automated/manual grading logic.

## Core rules

### 1. Explicit attempt state machine
- Model `QuizAttempt` and `CbtExamResult` with clear states (e.g., `in_progress`, `submitted`, `graded`, `expired`).
- Once an attempt transitions to `submitted` or `graded`, reject any further answer mutations.
- Keep `started_at`, `expires_at`, and `submitted_at` as server-authoritative timestamps.

### 2. Server-authoritative timer & safe autosave
- Calculate remaining time from the server (`expires_at - now()`); sync client countdowns from server timestamps, never trust client-submitted remaining seconds.
- Debounce autosave requests on the client and persist only changed answers on the server (avoid rewriting the entire answer payload on every keystroke/click).
- Allow a small configurable grace window only if explicitly required by exam rules, enforced server-side.

### 3. Prevent race conditions on submit & grading
- Wrap final submission and score calculation in `DB::transaction(...)` with row-level locking (`lockForUpdate()`) on the attempt/result record to prevent double-submit or concurrent autosave overwrites.
- Make final submission idempotent: if already submitted, return the existing result cleanly without recalculating or throwing a 500 error.

### 4. Deterministic randomization & question versioning
- Store a deterministic seed or ordered question/option ID snapshot per attempt (`QuizAttempt` / `CbtExamResult`) so page reloads do not reshuffle questions or options mid-exam.
- When updating or deleting items in `QuestionBank` or `QuestionStimulus`, preserve historical integrity via `QuestionItemHistory` or snapshotted question content so past `QuizAttempt` / `CbtExamResult` scores remain auditable.

### 5. Remedial & enrichment flows
- Link `RemedialEnrichment` outcomes cleanly to the source assessment without mutating original attempt audit records.

## Checklist before finishing

- [ ] Attempt state transitions are guarded and idempotent.
- [ ] Final submit uses `DB::transaction` + `lockForUpdate()`.
- [ ] Question/option order persists across page refreshes for the same attempt.
- [ ] Editing `QuestionBank` does not corrupt historical `QuizAttempt` or `CbtExamResult` scores.
