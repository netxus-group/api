# CI4 CORS Setup (Development and Alignment)

## Required Dev Origins
Allow these frontend origins in development:
- `http://localhost:3000` (portal)
- `http://localhost:3001` (alternate portal)
- `http://localhost:3002` (dashboard)
- `http://localhost:5173` (Vite portal)
- `http://localhost:5174` (Vite dashboard)

In CI4 `.env`:

```ini
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:3001,http://localhost:3002,http://localhost:5173,http://localhost:5174
```

## Required Methods and Headers
CORS must allow at least:
- Methods: `OPTIONS, GET, POST, PUT, PATCH, DELETE`
- Headers: `Authorization, Content-Type, Accept`

## Current CI4 Implementation Points

### 1) Global filter registration
File: `app/Config/Filters.php`
- Ensure `cors` is present in both:
  - `$globals['before']`
  - `$globals['after']`

### 2) CORS config values
File: `app/Config/Cors.php`
- `allowedOrigins` from `CORS_ALLOWED_ORIGINS`
- `allowedMethods` includes `GET, POST, PUT, DELETE, OPTIONS, PATCH`
- `allowedHeaders` includes `Authorization, Content-Type, Accept`
- `allowCredentials` according to auth strategy

### 3) Filter logic (preflight + response headers)
File: `app/Filters/CorsFilter.php`

Expected behavior:
- For `OPTIONS`, return `204` and include all CORS headers.
- For non-OPTIONS requests, include CORS headers in `after()`.
- Set:
  - `Access-Control-Allow-Origin`
  - `Access-Control-Allow-Headers`
  - `Access-Control-Allow-Methods`
  - `Access-Control-Max-Age`
  - `Access-Control-Allow-Credentials` (if enabled)

## Important 404 Case
A common browser symptom is `CORS` while root cause is actually `404`.
If the 404 response omits CORS headers, browser reports a CORS failure first.

Recommendation:
- Ensure custom 404 responses also include CORS headers.
- In this repo, `app/Config/Routes.php` was updated so `set404Override(...)` adds CORS headers when the request origin is allowed.

## Concrete Minimal Example

```php
// app/Filters/CorsFilter.php (conceptual)
public function before(RequestInterface $request)
{
    if ($request->getMethod() === 'options') {
        $response = service('response');
        $response->setStatusCode(204);
        // set Access-Control-Allow-* headers
        return $response;
    }

    return null;
}

public function after(RequestInterface $request, ResponseInterface $response)
{
    // set Access-Control-Allow-* headers
}
```

## Verification Checklist

1. Browser Network tab:
- `OPTIONS` preflight returns `204`.
- API response includes `Access-Control-Allow-Origin` for allowed origins.

2. Validate both frontends:
- Portal at `http://localhost:3000` can call CI4 public/portal-user endpoints.
- Dashboard at `http://localhost:3002` can call CI4 admin endpoints.

3. Validate not-found behavior:
- Call a fake endpoint and verify the 404 still includes CORS headers.

## Notes About Credentials
If you use cookie auth (`credentials: include`):
- Do not use wildcard `*` origin.
- Return exact allowed origin.
- Keep `Access-Control-Allow-Credentials: true`.
