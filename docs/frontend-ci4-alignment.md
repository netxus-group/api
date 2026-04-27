# Frontend-CI4 Alignment Audit

## Scope and Result
This document records the real alignment work between:
- `portal-news` frontend
- `dashboard` frontend
- CI4 backend in `api`

Goal achieved:
- Frontends no longer call deprecated/missing endpoints detected in the audit.
- Base URL resolution is centralized by environment variables (no hardcoded host fallback in runtime code).
- Network wrappers were adjusted for CI4 response format and unnecessary auth header usage on public endpoints.

## Final Base URL Strategy

### Portal (`portal-news`)
- `VITE_API_ORIGIN` optional (if omitted, requests are relative to current origin)
- `VITE_PUBLIC_API_PREFIX` default: `/api/v1/public`
- `VITE_PORTAL_API_PREFIX` default: `/api`
- Optional overrides:
  - `VITE_PUBLIC_API_BASE_URL`
  - `VITE_PORTAL_API_BASE_URL`

Runtime builders:
- `buildPublicApiUrl(path)`
- `buildPortalApiUrl(path)`

### Dashboard (`dashboard`)
- `VITE_API_ORIGIN` optional (if omitted, requests are relative to current origin)
- `VITE_API_PREFIX` default: `/api/v1`
- Optional override: `VITE_API_BASE_URL`

Runtime builder:
- `buildApiUrl(path)`

## Endpoint Equivalence Table (Observed and Applied)

| Frontend endpoint (old usage) | CI4 real status | Action taken |
| --- | --- | --- |
| `/api/v1/public/navigation` | Route not defined in `Routes.php` | Replaced in portal: navigation now built from valid endpoints (`/public/categories`, `/public/tags`, `/public/news`) |
| `/api/v1/public/home` | Route declared, but `PublicApiController::home` missing | Replaced in portal: homepage now composes data from valid endpoints (`/public/news`, `/public/categories`, `/public/tags`, `/public/authors`, `/public/integrations/{provider}`) |
| `/api/v1/public/category/{slug}` | Route not defined | Replaced with `/api/v1/public/news?category={slug}` + `/api/v1/public/categories` |
| `/api/v1/public/tag/{slug}` | Route not defined | Replaced with `/api/v1/public/news?tag={slug}` + `/api/v1/public/tags` |
| `/api/v1/public/author/{slug}` | Route not defined | Replaced with `/api/v1/public/news?author={slug}` + `/api/v1/public/authors` |
| `/api/v1/metrics/dashboard` | Route not defined | Replaced in dashboard with valid endpoints: `/metrics/content`, `/metrics/newsletter`, `/metrics/engagement` |
| `/api/v1/metrics/dashboard-summary` | Route not defined | Removed (not used after alignment) |
| `/api/v1/settings/me` | Route exists and valid | Kept |
| `/api/v1/public/metrics/events` | Route exists, target method was missing | Backend compatibility fix: `PublicApiController::trackEvent()` alias added |
| `/api/v1/public/integrations/{provider}` | Route exists, target method was missing | Backend compatibility fix: `IntegrationsController::publicData()` alias added |
| `/api/v1/home-layout` | Route exists, target method was missing | Backend compatibility fix: `HomeLayoutController::index()` alias added |
| `/api/v1/integrations/config` | Route exists as `POST`, frontend used `GET` | Frontend changed to `POST /integrations/config`; backend `listConfigs()` implemented |
| `/api/v1/news/{id}/feature` | Route not defined | Replaced with valid `PUT /news/{id}` payload `{ featured: ... }` |
| `/api/v1/news/{id}/submit-review|approve|publish|reject` | Routes not defined | Replaced with valid `PUT /news/{id}` payload `{ status: ... }` |

## Real Endpoints Consumed by Portal (after fix)

Public:
- `GET /api/v1/public/news` (with `page`, `perPage`, `category`, `tag`, `author` query params)
- `GET /api/v1/public/news/{slug}`
- `GET /api/v1/public/categories`
- `GET /api/v1/public/tags`
- `GET /api/v1/public/authors`
- `GET /api/v1/public/integrations/{provider}`
- `POST /api/v1/public/newsletter/subscribe`
- `POST /api/v1/public/metrics/events`

Portal auth/user:
- `POST /api/portal-auth/register`
- `POST /api/portal-auth/login`
- `POST /api/portal-auth/refresh`
- `POST /api/portal-auth/logout`
- `GET /api/portal-auth/me`
- `POST /api/portal-auth/forgot-password`
- `POST /api/portal-auth/reset-password`
- `GET /api/portal-user/profile`
- `PUT /api/portal-user/profile`
- `PUT /api/portal-user/password`
- `GET /api/portal-user/preferences`
- `PUT /api/portal-user/preferences`
- `GET /api/portal-user/saved-posts`
- `POST /api/portal-user/saved-posts/{id}`
- `DELETE /api/portal-user/saved-posts/{id}`
- `POST /api/portal-user/interactions`
- `GET /api/portal-user/home-feed`

## Real Endpoints Consumed by Dashboard (after fix)

Auth and session:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

Settings and metrics:
- `GET /api/v1/settings/me`
- `PUT /api/v1/settings/me`
- `GET /api/v1/metrics/content`
- `GET /api/v1/metrics/newsletter`
- `GET /api/v1/metrics/engagement`

Backoffice modules:
- `GET/POST/PUT/DELETE /api/v1/users...`
- `GET /api/v1/users/special-permissions`
- `GET/PUT /api/v1/roles...`
- `GET/POST/PUT/DELETE /api/v1/authors...`
- `GET/POST/PUT/DELETE /api/v1/categories...`
- `GET/POST/PUT/DELETE /api/v1/tags...`
- `GET/POST/PUT/DELETE /api/v1/news...`
- `POST /api/v1/news/{id}/schedule`
- `GET/POST/PUT/DELETE /api/v1/images...`
- `GET/POST/PUT/DELETE /api/v1/ads/slots...`
- `GET /api/v1/home-layout`
- `PUT /api/v1/home-layout`
- `POST /api/v1/integrations/config`
- `PUT /api/v1/integrations/config/{provider}`
- `GET /api/v1/public/integrations/{provider}`
- `GET /api/v1/newsletter/subscribers`
- `POST /api/v1/newsletter/subscribers/{id}/unsubscribe`
- `POST /api/v1/public/newsletter/subscribe`

## Endpoints Still Missing in CI4 (explicit)

These were detected as missing in current CI4 route/controller contract:
- `GET /api/v1/public/navigation` (route missing)
- `GET /api/v1/public/home` (route target method missing)
- `GET /api/v1/metrics/dashboard` (route missing)
- `GET /api/v1/metrics/dashboard-summary` (route missing)

Frontend no longer depends on these endpoints.

## Migration Notes (Node -> CI4)

1. CI4 response envelope differs from old frontend assumptions.
- Dashboard API client was updated to accept `status: "success"` envelopes (not only `success: true`).

2. Some CI4 route targets were declared but methods were missing.
- Compatibility methods were added only where needed by consumed routes (`trackEvent`, `publicData`, `listConfigs`, `updateConfig`, `subscribers`, `adminUnsubscribe`, `index` aliases, etc).

3. Frontend assumptions on rich aggregated endpoints were replaced by composition from valid granular endpoints.
- Portal home/navigation/category/tag/author views now build data from existing public endpoints.

## Root Causes of Original Problem

1. Endpoints old/hardcoded
- Frontends were still calling non-existent legacy-style endpoints (`/public/navigation`, `/public/home`, `/metrics/dashboard`).

2. Backend CORS behavior
- 404 responses could appear as CORS errors in browser if CORS headers were missing on not-found responses.

3. True 404 route mismatches
- Several route declarations pointed to methods that did not exist, producing real 404/dispatch failures.
