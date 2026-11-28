---
name: lms-ui-alpine-a11y
description: "Build accessible, responsive Blade + Livewire + Alpine.js + Tailwind CSS v4 UI components for LMS workflows. Apply whenever creating or updating interactive views, modals, exam/CBT interfaces, forms, tables, or navigation for students, teachers, and admins."
license: MIT
metadata:
  author: lms-project
---

# LMS UI, Alpine.js & Accessibility

Guides consistent, accessible, and responsive UI development with Blade, Livewire 4, Alpine.js, and Tailwind CSS v4.

## When to apply

- Building or refactoring Blade views, Livewire component templates, modals, drawers, tables, or exam/quiz interfaces.
- Adding client-side interactivity with Alpine.js (`x-data`, `x-show`, `x-trap`, etc.).

## Core rules

### 1. Livewire + Alpine.js boundaries
- Use Alpine.js for purely client-side UI state (dropdowns, tabs, modals, countdown display, sidebar toggles) to avoid unnecessary Livewire round-trips.
- Use `$wire` / `wire:model` only for state that must persist or trigger server validation/rendering.
- Always use `wire:key` on loops (`@foreach`) rendering Livewire components or dynamic rows (questions, submissions, attendance lists).

### 2. Feedback & destructive action safety
- Show clear loading states (`wire:loading`, disabled submit buttons during requests) so students/teachers do not double-submit assignments or grades.
- Display non-intrusive status indicators for autosave actions (e.g., "Menyimpan...", "Tersimpan", "Gagal menyimpan — coba lagi").
- Require explicit confirmation modals for destructive or irreversible actions (finalizing `CbtExamResult`, deleting `CourseModule`, resetting `QuizAttempt`).

### 3. Accessibility (a11y) & keyboard navigation
- Use semantic HTML (`<button>`, `<nav>`, `<main>`, `<label for="...">`, `<table>` with `<thead>`/`<th scope>`). Never use clickable `<div>` elements without `role="button"`, `tabindex="0"`, and keyboard handlers.
- Ensure modals trap focus and close on `Escape` (except mandatory exam lock modals where intentional).
- Support keyboard navigation on CBT/Quiz interfaces (e.g., radio/checkbox groups properly associated with labels, visible `focus-visible:` rings).
- Maintain readable contrast and clear error messages (`aria-invalid`, `aria-describedby`) on all form inputs.

### 4. Responsive academic layouts
- Ensure student-facing views (materials, quizzes, assignments, attendance) work cleanly on mobile viewports, and wide teacher gradebooks/tables scroll horizontally (`overflow-x-auto`) without breaking page layout.

## Checklist before finishing

- [ ] `wire:key` is present on all dynamic `@foreach` items.
- [ ] Loading/disabled states prevent accidental double submissions.
- [ ] All interactive controls are keyboard-accessible with visible focus states.
- [ ] Tables and forms work cleanly on mobile and desktop screens.
