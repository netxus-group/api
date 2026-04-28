# Deploy automation

Este repositorio usa `.github/workflows/deploy.yml`.

## Trigger
- Push a `main`
- Ejecucion manual con `workflow_dispatch`

## Secrets requeridos
- `FTP_SERVER`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- `FTP_PORT`
- `FTP_REMOTE_DIR` (opcional, default: `/public_html/api/`)
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `APP_BASE_URL` (opcional, default: `https://api.netxus.com.ar/`)
- `DASHBOARD_SITE_URL` (opcional, default: `https://admin.netxus.com.ar`)
- `PUBLIC_SITE_URL` (opcional, default: `https://netxus.com.ar`)
- `CORS_ALLOWED_ORIGINS` (opcional, default: `https://netxus.com.ar,https://api.netxus.com.ar,https://admin.netxus.com.ar`)

## Comportamiento
- No compila Node para produccion.
- Instala dependencias Composer (`--no-dev`) para incluir `vendor`, necesario para CodeIgniter.
- Arma un staging de deploy excluyendo `.git`, `.github`, `node_modules`, `tests`, `docs`, `sql`, `.env*`, temporales y archivos locales.
- Publica por FTP en `FTP_REMOTE_DIR` (si no se define usa `/public_html/api/`) sin borrado destructivo (`mirror -R` sin `--delete`).
- En `workflow_dispatch` podes usar `upload_env=true` para generar y subir `.env` desde los secrets `DB_*`.
- Si `upload_env=true`, tambien inyecta URLs/CORS productivos para `netxus.com.ar`, `api.netxus.com.ar` y `admin.netxus.com.ar` (o los overrides por secrets).
- En deploy automatico por push no se sobreescribe `.env`, para preservar la configuracion sensible existente del servidor.
