---
name: lms-security-authorization
description: "Enforce LMS-specific authorization, Livewire state protection, deadline/attempt enforcement, and file upload security across roles (student, teacher, admin). Apply whenever creating or modifying Policies, Gates, Livewire components, controllers, or file handlers for Course, Classroom, Assignment, AssignmentSubmission, Quiz, QuizAttempt, CbtExam, CbtExamResult, QuestionBank, or Material."
license: MIT
metadata:
  author: lms-project
---

# LMS Security & Authorization

Protects multi-role academic data, prevents IDOR, and secures Livewire state in this Laravel 13 + Livewire 4 LMS.

## When to apply

- Creating or updating `Policy` classes, `Gate` definitions, or route/component authorization checks.
- Building or refactoring Livewire components that handle `Course`, `Classroom`, `Assignment`, `AssignmentSubmission`, `Quiz`, `QuizAttempt`, `CbtExam`, `CbtExamResult`, `QuestionBank`, or `Material`.
- Handling student/teacher file uploads or downloadable resources.

## Core rules

### 1. Prevent IDOR on academic resources
- Always authorize against the authenticated user's actual relationship (`CourseTeacher`, `CourseEnrollment`, `ClassroomStudent`, `UserAssignment`), never trust raw route/request IDs alone.
- Scope queries to the authorized relationship or call `$this->authorize(...)` before reading or mutating any model.
- Students may only view/submit `AssignmentSubmission`, `QuizAttempt`, and `CbtExamResult` records they own, and only within courses/classrooms they are enrolled in.
- Teachers may only grade or manage courses/classrooms they are assigned to via `CourseTeacher` / `UserAssignment`.

### 2. Protect Livewire public properties
- Mark database IDs, ownership keys, exam/attempt identifiers, and immutable state with `#[Locked]`.
- **Never** expose answer keys, grading rubrics, or hidden `QuestionBank` / `QuizQuestion` fields in Livewire public properties or serialized payloads sent to the browser.
- Re-verify authorization inside every Livewire action (`#[Renderless]` or standard action methods), not only in `mount()`.

### 3. Enforce deadlines & attempt limits server-side
- Never rely on disabled UI buttons or client-side timers for deadlines or attempt limits.
- Validate `now()` against assignment due dates, exam windows, and maximum `QuizAttempt` / `CbtExamResult` counts on the server inside the submit/autosave action.
- Reject modifications to `AssignmentSubmission`, `QuizAttempt`, or `CbtExamResult` once finalized/submitted.

### 4. Secure file uploads & downloads
- Store sensitive submissions and private materials on non-public disks (`local` / private storage) and serve them through authorized controller/download responses, not direct `public/` URLs.
- Validate MIME types, extensions, and max file sizes on both upload and storage; sanitize filenames before saving.
- Never allow students to download other students' `AssignmentSubmission` files.

## Checklist before finishing

- [ ] Every new/edited endpoint or Livewire action has explicit role + ownership authorization.
- [ ] Sensitive IDs on Livewire components use `#[Locked]`.
- [ ] Answer keys and hidden metadata are excluded from frontend payloads.
- [ ] Deadlines and attempt limits are enforced server-side.
