---
name: lms-performance-sync
description: "Optimize database queries, caching, background jobs, notifications, and external data synchronization for high-concurrency LMS workloads. Apply whenever writing queries across nested academic relations, working with SyncLog, ActivityLog, MaterialProgress, LmsNotification, or building dashboards/recaps."
license: MIT
metadata:
  author: lms-project
---

# LMS Performance, Queues & Synchronization

Keeps dashboards, grade/attendance recaps, and sync pipelines fast under concurrent school/exam load.

## When to apply

- Writing queries spanning nested academic models (`AcademicYear`, `Classroom`, `ClassroomStudent`, `Course`, `CourseModule`, `Material`, `MaterialProgress`, `LearningAttendance`, `SchoolAttendance`).
- Implementing or modifying `SyncLog`, `ActivityLog`, or `LmsNotification` workflows.
- Building summary/recap pages or bulk operations.

## Core rules

### 1. Eliminate N+1 queries & over-fetching
- Eager load required relations (`with(...)`, `loadMissing(...)`) and select only needed columns when rendering lists, gradebooks, or attendance tables.
- Prefer database aggregates (`withCount`, `withAvg`, `whereHas`, `DB::table` / subqueries) over loading full Eloquent collections into PHP memory just to count or sum.
- Paginate all list views (`paginate()` or `simplePaginate()`); never call `->get()` on unbounded tables (`ActivityLog`, `SyncLog`, `LmsNotification`, `AssignmentSubmission`).

### 2. Indexing & concurrency hotspots
- Ensure composite indexes exist on frequent filter/join columns (e.g., `(academic_year_id, classroom_id)`, `(course_id, user_id)`, `(cbt_exam_id, user_id)`).
- Avoid synchronous heavy writes on hot paths (e.g., logging `MaterialProgress` or `ActivityLog` on every view): keep writes minimal, indexed, and offload non-critical side effects to queued jobs.

### 3. Queues & notifications (`LmsNotification`)
- Dispatch bulk notifications (e.g., new `Announcement`, `Assignment`, or `CbtExam` published to an entire `Classroom` or `Course`) via queued Jobs / `Bus::batch()`, never in a synchronous `foreach` loop inside an HTTP/Livewire request.
- Make queued jobs idempotent (`ShouldBeUnique` or unique key checks) and configure explicit `$tries` and `$backoff`.

### 4. Reliable synchronization (`SyncLog`)
- Record every sync run in `SyncLog` with clear status, source, payload summary/count, timestamps, and error details.
- Make sync operations **idempotent** using `upsert` / `updateOrCreate` keyed by stable external/composite identifiers so retries never duplicate `ClassroomStudent`, `Course`, or `User` records.
- Always set HTTP client timeouts (`Http::timeout(...)`) and handle partial failures gracefully.

## Checklist before finishing

- [ ] No N+1 queries or unbounded `->get()` calls on list/recap pages.
- [ ] Bulk `LmsNotification` dispatches run on queues.
- [ ] `SyncLog` operations are idempotent and safe to retry.
