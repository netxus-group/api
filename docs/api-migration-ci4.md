# Frontend + API Migration Notes (CI4)

## Scope
This document summarizes the frontend integration changes required after migrating the backend from Node to CodeIgniter 4, and the CORS requirements that must be configured in CI4.

## Frontend Base URL Strategy

### Dashboard (`dashboard`)
- Runtime API base is now resolved from:
  - `VITE_API_BASE_URL` (optional explicit override), or
  - `VITE_API_ORIGIN` + `VITE_API_PREFIX`.
- Recommended defaults:
  - `VITE_API_ORIGIN=http://localhost:8080`
  - `VITE_API_PREFIX=/api/v1`
- URL construction is centralized with `buildApiUrl(path)` and normalized to avoid duplicated or missing slashes.

### Portal News (`portal-news`)
- Runtime API bases are now resolved from:
  - Optional explicit overrides:
    - `VITE_PUBLIC_API_BASE_URL`
    - `VITE_PORTAL_API_BASE_URL`
  - Or from `VITE_API_ORIGIN` + prefixes:
    - `VITE_PUBLIC_API_PREFIX` (default `/api/v1/public`)
    - `VITE_PORTAL_API_PREFIX` (default `/api`)
- URL construction is centralized with:
  - `buildPublicApiUrl(path)`
  - `buildPortalApiUrl(path)`

## Endpoint Alignment with CI4

### Confirmed mappings applied
- Dashboard summary:
  - Old frontend call: `GET /metrics/dashboard-summary?range=...`
  - Updated call: `GET /metrics/dashboard?range=...`
- Custom date range query keys:
  - Old keys: `startDate`, `endDate`
  - Updated keys: `from`, `to`
- Integration test request from dashboard:
  - Old call: `GET /integrations/data/{provider}`
  - Updated call: `GET /public/integrations/{provider}`

### Confirmed still valid
- `GET /settings/me`
- `PUT /settings/me`
- Authenticated dashboard calls under `/api/v1/*`
- Portal auth and portal-user calls under `/api/*`

## Request Behavior and CORS-Safe Adjustments

To avoid unnecessary preflight noise and reduce CORS complexity:
- `Content-Type: application/json` is now only sent when a JSON body is present.
- `GET` and `DELETE` requests no longer force `Content-Type`.
- `Accept: application/json` is still sent.
- Frontends do not require `credentials: include` for current token flow (Bearer token headers).

## Required CI4 CORS Configuration

Even with frontend fixes, CORS must be correctly configured in backend CI4.
Without this, browser requests will still fail with:
`No 'Access-Control-Allow-Origin' header is present on the requested resource`.

### 1) Allow frontend origins in `.env`
Set `CORS_ALLOWED_ORIGINS` to every frontend origin used in each environment.

Example (local):

```ini
CORS_ALLOWED_ORIGINS = http://localhost:3000,http://localhost:3001,http://localhost:3002,http://localhost:5173,http://localhost:5174
```

Example (staging/prod):

```ini
CORS_ALLOWED_ORIGINS = https://staging-admin.netxus.com.ar,https://staging-netxus.com.ar,https://admin.netxus.com.ar,https://netxus.com.ar,https://api.netxus.com.ar
```

### 2) Keep CORS filter globally enabled
In `app/Config/Filters.php`, ensure `cors` is applied in both `before` and `after` globals.

### 3) Ensure allowed methods and headers include frontend needs
In `app/Config/Cors.php`, allow at least:
- Methods: `GET, POST, PUT, PATCH, DELETE, OPTIONS`
- Headers: `Authorization, Content-Type, Accept, Origin`

### 4) Handle preflight OPTIONS
Your `App\Filters\CorsFilter` should return `204` for `OPTIONS` and include CORS headers.
This is required for authenticated requests that send `Authorization`.

### 5) Credentials rule (important)
If requests ever move to cookies/session auth (`credentials: include`), you must:
- keep explicit origins (no `*`), and
- return `Access-Control-Allow-Credentials: true`.

## Quick Validation Checklist
- Browser DevTools Network shows:
  - successful `OPTIONS` (204/200) when preflight is needed
  - `Access-Control-Allow-Origin` in API responses
- Dashboard can load:
  - `/api/v1/settings/me`
  - `/api/v1/metrics/dashboard`
- Portal can load:
  - `/api/v1/public/*`
  - `/api/portal-auth/*` and `/api/portal-user/*`

## Known Backend Route Inconsistencies to Validate
There are route/controller naming mismatches in current CI4 source (outside frontend scope) that should be verified server-side:
- Some `app/Config/Routes.php` entries reference controller methods that do not exist with the same name.
- Integration and metrics route names/method signatures should be smoke-tested with real requests after deploy.

Frontend now targets the CI4-style paths listed above, but backend route definitions still need consistency checks to guarantee all modules respond as expected.
