# Deploy automation

Este repositorio usa `.github/workflows/deploy.yml`.

## Trigger
- Push a `main`
- Ejecucion manual con `workflow_dispatch`

## Repository Variables requeridas
- `FTP_SERVER`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- `FTP_PORT`
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `JWT_SECRET`
- `JWT_REFRESH_SECRET`
- `PORTAL_JWT_SECRET`
- `PORTAL_JWT_REFRESH_SECRET`
- `PUBLIC_API_SECRET`
- `NEWSLETTER_UNSUBSCRIBE_SECRET`

## Repository Variables opcionales
- `FTP_REMOTE_DIR` (default: `/public_html/api/`)
- `CI_ENVIRONMENT` (default: `production`)
- `APP_BASE_URL` (default: `https://api.netxus.com.ar/`)
- `APP_TIMEZONE` (default: `America/Argentina/Buenos_Aires`)
- `DASHBOARD_SITE_URL` (default: `https://admin.netxus.com.ar`)
- `PUBLIC_SITE_URL` (default: `https://netxus.com.ar`)
- `CORS_ALLOWED_ORIGINS` (default: `https://netxus.com.ar,https://www.netxus.com.ar,https://api.netxus.com.ar,https://admin.netxus.com.ar`)
- `DB_PORT` (default: `3306`)
- `JWT_ACCESS_EXPIRES` (default: `900`)
- `JWT_REFRESH_EXPIRES` (default: `604800`)
- `PORTAL_JWT_ACCESS_EXPIRES` (default: `900`)
- `PORTAL_JWT_REFRESH_EXPIRES` (default: `1209600`)
- `PORTAL_PASSWORD_RESET_EXPIRES` (default: `3600`)
- `PORTAL_BCRYPT_COST` (default: `10`)
- `PORTAL_MAX_LOGIN_ATTEMPTS` (default: `8`)
- `PORTAL_LOGIN_ATTEMPT_WINDOW` (default: `300`)
- `UPLOADS_PATH` (default: `writable/uploads/`)
- `UPLOADS_PUBLIC_URL` (default: `/uploads/`)
- `UPLOADS_MAX_SIZE` (default: `8192`)
- `EMAIL_PROTOCOL`
- `EMAIL_SMTP_HOST`
- `EMAIL_SMTP_USER`
- `EMAIL_SMTP_PASS`
- `EMAIL_SMTP_PORT`
- `EMAIL_SMTP_CRYPTO`
- `EMAIL_MAIL_TYPE`
- `MAIL_PROVIDER`
- `MAIL_SEND_ENABLED`
- `MAIL_TEST_MODE`
- `MAIL_TEST_EMAIL`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `MAIL_REPLY_TO`
- `MAIL_WELCOME_FROM_ADDRESS`
- `MAIL_WELCOME_FROM_NAME`
- `MAIL_WELCOME_REPLY_TO`
- `MAIL_NEWSLETTER_FROM_ADDRESS`
- `MAIL_NEWSLETTER_FROM_NAME`
- `MAIL_NEWSLETTER_REPLY_TO`
- `SMTP_HOST`
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_PORT`
- `SMTP_ENCRYPTION`
- `ENVIALO_SIMPLE_API_KEY`
- `ENVIALO_SIMPLE_API_URL`
- `ENVIALO_SIMPLE_ACCOUNT_ID`
- `WEATHER_API_URL` (default: `https://api.open-meteo.com/v1/forecast`)
- `WEATHER_LATITUDE` (default: `-34.6037`)
- `WEATHER_LONGITUDE` (default: `-58.3816`)
- `CURRENCY_API_URL` (default: `https://open.er-api.com/v6/latest/USD`)

## Comportamiento
- No compila Node para produccion.
- Instala dependencias Composer (`--no-dev`) para incluir `vendor`, necesario para CodeIgniter.
- Arma un staging de deploy excluyendo `.git`, `.github`, `node_modules`, `tests`, `docs`, `sql`, `.env*`, temporales y archivos locales.
- Genera siempre `.env` en el paquete de deploy, partiendo de `.env.example` y reemplazando claves con `vars.*`.
- Publica por FTP en `FTP_REMOTE_DIR` (si no se define usa `/public_html/api/`) sin borrado destructivo (`mirror -R` sin `--delete`).
- Como el deploy incluye el nuevo `.env`, el archivo remoto queda alineado con las variables configuradas en GitHub.
