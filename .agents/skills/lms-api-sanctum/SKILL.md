---
name: lms-api-sanctum
description: "Build consistent, secure REST APIs using Laravel Sanctum 4, Eloquent API Resources, and rate limiting. Apply whenever creating or modifying API routes, API controllers, JsonResource classes, or Sanctum token authentication for mobile apps, CBT clients, or external integrations."
license: MIT
metadata:
  author: lms-project
---

# LMS API & Sanctum Patterns

Standardizes REST API endpoints powered by `laravel/sanctum` (`^4.0`) in Laravel 13.

## When to apply

- Creating or updating routes in `routes/api.php` or API controllers.
- Configuring Sanctum token issuance, abilities/scopes, or rate limiters.
- Transforming models into JSON responses.

## Core rules

### 1. API versioning & Eloquent Resources
- Namespace and prefix API routes consistently (e.g., `/api/v1/...`) unless existing routes in `routes/api.php` follow a different established convention.
- Always return `JsonResource` / `ResourceCollection` classes instead of raw Eloquent models so sensitive attributes (`password`, internal tokens, hidden exam answers) are never leaked accidentally.
- Use `FormRequest` classes for all API input validation.

### 2. Sanctum token hygiene & abilities
- Issue tokens with explicit `abilities` scoped to the user's role or client purpose (e.g., `cbt:take`, `sync:write`) and enforce them via middleware (`ability:` / `abilities:`) or `$user->tokenCan(...)`.
- Set appropriate token expiration (`expiration` in `config/sanctum.php` or per-token `expiresAt`) and revoke old/unused tokens on logout or password change.
- Never bypass `Policy` authorization just because a request carries a valid Sanctum token.

### 3. Rate limiting & error consistency
- Apply dedicated `RateLimiter` rules to sensitive endpoints: login/token issuance, CBT autosave/submit, and sync endpoints.
- Return consistent JSON error structures and proper HTTP status codes (`401` unauthenticated, `403` unauthorized, `404` not found, `422` validation error, `429` rate limited).

## Checklist before finishing

- [ ] Responses use `JsonResource` (no raw model dumps).
- [ ] Endpoints enforce both Sanctum authentication/abilities and resource `Policy`.
- [ ] Auth and high-frequency endpoints have explicit rate limits.
