---
name: lms-excel-reporting
description: "Build safe, memory-efficient Excel import and export features using phpoffice/phpspreadsheet. Apply whenever creating or modifying spreadsheet imports/exports for grades, attendance (SchoolAttendance, LearningAttendance), student rosters (ClassroomStudent), or QuestionBank items."
license: MIT
metadata:
  author: lms-project
---

# LMS Excel Import & Export (`phpoffice/phpspreadsheet`)

Provides safe, scalable patterns for spreadsheet processing using `phpoffice/phpspreadsheet` (`^5.12`).

## When to apply

- Importing student rosters (`ClassroomStudent`), question banks (`QuestionBank`), or schedules (`CourseSchedule`) from `.xlsx`/`.csv`.
- Exporting gradebooks, `CbtExamResult`, `QuizAttempt`, `SchoolAttendance`, or `LearningAttendance` reports.

## Core rules

### 1. Memory & performance
- Never load unbounded spreadsheets into memory inside a synchronous web request without limits.
- For imports: use `ReadFilter` / chunked row iteration, validate file size and max row count upfront, and wrap batch inserts/upserts in `DB::transaction(...)`.
- For large exports (multi-class gradebooks, semester attendance): dispatch a queued Job, generate the file to storage, and notify the user (`LmsNotification`) when ready for download.

### 2. Strict import validation & error reporting
- Validate header columns against the expected template before processing rows.
- Validate each row (types, foreign keys like `AcademicYear`, `Classroom`, `Subject`, `User`, duplicate checks) and collect row-numbered error messages (`Baris 12: ...`) so teachers/admins can fix the file in one pass.
- Reject partial/corrupt imports atomically unless partial import is explicitly requested and reported.

### 3. Prevent CSV/Excel formula injection
- When exporting user-controlled strings (student names, discussion text, assignment comments, question text), neutralize leading formula characters (`=`, `+`, `-`, `@`, `\t`, `\r`) by prefixing with a single quote (`'`) or writing explicitly as `DataType::TYPE_STRING`.

### 4. Clean formatting & cleanup
- Use explicit column data types (`TYPE_STRING` for NISN/NIP/phone numbers/IDs) so leading zeros are preserved and numbers are not converted to scientific notation.
- Clean up temporary files (`unlink` / `Storage::delete`) after streaming or failed jobs.

## Checklist before finishing

- [ ] Imports validate headers and report row-level errors clearly.
- [ ] Large exports run in queued jobs rather than blocking HTTP requests.
- [ ] IDs/codes with leading zeros are written as strings.
- [ ] User-supplied cell values are protected against formula injection.
