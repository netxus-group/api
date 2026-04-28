# Netxus Portal News — API (CodeIgniter 4)

API REST backend para **Netxus Portal News**, portal de noticias con flujo editorial, encuestas, newsletter, métricas y más.

## Stack técnico

| Componente | Tecnología |
|---|---|
| Framework | CodeIgniter 4.5+ |
| PHP | 8.1+ |
| Base de datos | MySQL 8.0+ / MariaDB 10.6+ |
| Auth | JWT (firebase/php-jwt) |
| Exports | PhpSpreadsheet (Excel), DomPDF (PDF) |
| Hosting target | Shared hosting (DonWeb / Ferozo) |

## Requisitos previos

- PHP 8.1 o superior con extensiones: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`, `gd`, `openssl`
- Composer 2.x
- MySQL 8.0+ o MariaDB 10.6+
- Acceso SSH al hosting (recomendado) o panel de archivos

## Instalación local

```bash
# 1. Clonar / copiar el proyecto
cd api

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp .env.example .env
# Editar .env con datos de la base de datos, JWT secret, etc.

# 4. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE netxus_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Opción A: Ejecutar migraciones + seed (recomendado)
php spark migrate
php spark db:seed DatabaseSeeder

# 5. Opción B: Importar SQL directamente
mysql -u root -p netxus_portal < sql/schema.sql
mysql -u root -p netxus_portal < sql/seed-initial.sql

# 6. (Opcional) Cargar datos de prueba
php spark db:seed TestDataSeeder
# O por SQL:
mysql -u root -p netxus_portal < sql/test-data.sql

# 7. Iniciar servidor de desarrollo
php spark serve --port 8080
```

## Levantar con Docker (desde `api/`)

```bash
# 1. Estando dentro de la carpeta api/
cd api

# 2. Configurar entorno
cp .env.example .env

# 3. Levantar API + MySQL
docker compose up -d --build

# 4. Ejecutar migraciones y seed inicial
docker compose exec api php spark migrate
docker compose exec api php spark db:seed DatabaseSeeder
```

La API queda disponible en `http://localhost:8080`.

Para detener contenedores:

```bash
docker compose down
```

## Configuración del .env

Variables clave a configurar:

```ini
# Base de datos
database.default.hostname = localhost
database.default.database = netxus_portal
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi

# JWT
JWT_SECRET = tu-clave-secreta-minimo-32-caracteres
JWT_ACCESS_TTL = 900    # 15 minutos
JWT_REFRESH_TTL = 604800 # 7 días

# CORS (separar orígenes con coma)
CORS_ALLOWED_ORIGINS = http://localhost:3000,http://localhost:3001,http://localhost:3002,http://localhost:5173,http://localhost:5174

# Uploads
UPLOAD_PATH = writable/uploads/
UPLOAD_MAX_SIZE = 5242880  # 5MB
UPLOAD_ALLOWED_TYPES = image/jpeg,image/png,image/webp,image/gif

# URL base de la API
app.baseURL = http://localhost:8080/
```

## Estructura del proyecto

```
api/
├── app/
│   ├── Commands/           # Comandos CLI para cron
│   ├── Config/             # Rutas, auth, filtros, validación
│   ├── Controllers/        # 16 controllers REST
│   ├── Database/
│   │   ├── Migrations/     # 22 migraciones
│   │   └── Seeds/          # Seeders (initial + test)
│   ├── Entities/           # 9 entidades
│   ├── Filters/            # Auth JWT, Role, CORS
│   ├── Libraries/          # JWT Manager, Slug, ApiResponse
│   ├── Models/             # ~25 modelos
│   └── Services/           # 7 servicios de lógica
├── public/                 # Front controller + .htaccess
├── sql/                    # Scripts SQL puros
├── writable/               # Logs, cache, uploads
├── composer.json
├── .env.example
└── spark                   # CLI entry point
```

## Módulos y endpoints

### Auth (`/api/v1/auth`)
| Método | Ruta | Descripción |
|---|---|---|
| POST | `/login` | Login con email + password → JWT |
| POST | `/refresh` | Renovar access token |
| POST | `/logout` | Revocar refresh token |
| GET | `/me` | Perfil del usuario autenticado |

### Usuarios (`/api/v1/users`) — super_admin
| Método | Ruta | Descripción |
|---|---|---|
| GET | `/` | Listar usuarios |
| POST | `/` | Crear usuario |
| GET | `/:id` | Ver perfil |
| PUT | `/:id` | Actualizar datos |
| PUT | `/:id/access` | Activar/desactivar |
| DELETE | `/:id` | Eliminar usuario |
| GET | `/:id/special-permissions` | Permisos especiales |

### Roles (`/api/v1/roles`) — super_admin
| GET | `/` | Listar roles |
| PUT | `/:id` | Actualizar capabilities |

### Noticias (`/api/v1/news`) — auth required
| Método | Ruta | Descripción |
|---|---|---|
| GET | `/` | Listar (writers ven solo propias) |
| POST | `/` | Crear artículo |
| GET | `/:id` | Detalle |
| PUT | `/:id` | Actualizar (con flujo editorial) |
| PUT | `/:id/schedule` | Programar publicación |
| DELETE | `/:id` | Eliminar (soft delete) |

### API Pública (`/api/v1/public`) — sin auth
| GET | `/news` | Noticias publicadas (paginadas) |
| GET | `/news/:slug` | Detalle por slug |
| GET | `/categories` | Categorías activas |
| GET | `/tags` | Tags activos |
| GET | `/authors` | Autores activos |
| GET | `/ads` | Publicidades activas |
| GET | `/home-layout` | Layout de portada |
| GET | `/integrations/:provider` | Datos de integración |
| POST | `/engagement` | Registrar evento |
| GET | `/polls/:id` | Encuesta con resultados |

### Otros módulos
- **Autores** (`/api/v1/authors`): CRUD con generación de slug
- **Categorías** (`/api/v1/categories`): CRUD con slug
- **Tags** (`/api/v1/tags`): CRUD con slug
- **Media** (`/api/v1/images`): Upload base64, CRUD
- **Ads** (`/api/v1/ads`): CRUD de espacios publicitarios
- **Encuestas** (`/api/v1/polls`): CRUD + respuestas anónimas
- **Newsletter** (`/api/v1/newsletter`): Suscripción, confirmación, gestión admin
- **Integraciones** (`/api/v1/integrations`): Clima, dólar, estado, refresh
- **Home Layout** (`/api/v1/home-layout`): Configuración de portada
- **Métricas** (`/api/v1/metrics`): Dashboard, contenido, engagement
- **Settings** (`/api/v1/settings`): Por usuario y globales
- **Reportes** (`/api/v1/reports`): Export CSV/Excel/PDF/TXT, audit log

## Roles y permisos

| Rol | Capacidades |
|---|---|
| **super_admin** | Acceso total: usuarios, roles, settings, métricas, exports |
| **editor** | Gestión de contenido, publicación, categorías, tags, autores, media, encuestas, métricas |
| **writer** | Crear y editar noticias propias, subir imágenes |

### Flujo editorial
```
draft → in_review → approved → published
                  → scheduled → published (por cron)
```
- **Writer**: crea en `draft`, puede enviar a `in_review`
- **Editor**: puede aprobar (`approved`), publicar (`published`)
- **Super Admin**: puede saltar cualquier paso

## Comandos CLI (Cron)

```bash
# Publicar artículos programados (cada minuto o cada 5 min)
php spark news:publish-scheduled

# Refrescar integraciones externas (cada 30 min)
php spark integrations:refresh

# Limpiar tokens expirados (diario a las 3 AM)
php spark auth:clean-tokens
```

### Configuración de cron en hosting compartido

```cron
* * * * * cd /home/usuario/api && php spark news:publish-scheduled >> /dev/null 2>&1
*/30 * * * * cd /home/usuario/api && php spark integrations:refresh >> /dev/null 2>&1
0 3 * * * cd /home/usuario/api && php spark auth:clean-tokens >> /dev/null 2>&1
```

## Deploy en hosting compartido (DonWeb / Ferozo)

### 1. Estructura de archivos

```
/home/usuario/
├── api/              ← Código completo (fuera de public_html)
│   ├── app/
│   ├── vendor/
│   ├── writable/
│   └── ...
└── public_html/
    └── api/              ← Symlink o copia de public/
        ├── index.php     ← Modificar FCPATH
        └── .htaccess
```

### 2. Modificar `public/index.php` para hosting

Cambiar las rutas si el proyecto NO está en el directorio estándar:

```php
// En public/index.php, ajustar las rutas:
$pathsPath = '/home/usuario/api/app/Config/Paths.php';
```

### 3. Ajustar `app/Config/Paths.php`

```php
public string $systemDirectory = '/home/usuario/api/vendor/codeigniter4/framework/system';
public string $appDirectory    = '/home/usuario/api/app';
public string $writableDirectory = '/home/usuario/api/writable';
```

### 4. Permisos

```bash
chmod -R 775 writable/
chmod -R 775 public/uploads/
```

### 5. Base de datos

Crear la base de datos desde el panel del hosting (cPanel/Plesk) y configurar el `.env` con las credenciales proporcionadas.

### 6. CORS

Actualizar `CORS_ALLOWED_ORIGINS` en `.env` con los dominios de producción:
```ini
CORS_ALLOWED_ORIGINS = https://netxus.com.ar,https://api.netxus.com.ar,https://admin.netxus.com.ar
```

## Usuarios de prueba

| Email | Password | Rol |
|---|---|---|
| admin@netxus.com | Admin123! | super_admin |
| admin2@netxus.com | Test1234! | super_admin |
| editor1@netxus.com | Test1234! | editor |
| editor2@netxus.com | Test1234! | editor |
| writer1@netxus.com | Test1234! | writer |
| writer2@netxus.com | Test1234! | writer |

> **Nota**: Los usuarios de prueba solo están disponibles si se ejecutó el `TestDataSeeder`.

## Migración desde Node.js

Esta API es una reimplementación completa de la API original en Node.js/Express/PostgreSQL.

**Cambios clave**:
- PostgreSQL → MySQL (UUIDs como CHAR(36), JSONB → JSON, TIMESTAMPTZ → DATETIME)
- Express middleware → CI4 Filters
- Drizzle ORM → CI4 Models + Query Builder
- Scheduler basado en setInterval → Cron commands
- Base64 upload se mantiene compatible con frontends existentes

**Endpoints compatibles**: Todos los endpoints de la API original están mapeados 1:1 para que los frontends (dashboard React + portal-news React) funcionen sin cambios.

## Portal Users (Readers) - New Layer

This API now includes a fully separated reader-auth domain for public portal users.

### Separation from editorial users
- Editorial users keep using `users`, `user_roles`, `refresh_tokens`, `auth` filter and `/api/v1/*` dashboards endpoints.
- Portal readers use `portal_users`, `portal_user_sessions`, `portalAuth` filter and `/api/portal-*` endpoints.
- No role mixing between editorial and reader authentication.

### New endpoints

#### Portal Auth
- `POST /api/portal-auth/register`
- `POST /api/portal-auth/login`
- `POST /api/portal-auth/refresh`
- `POST /api/portal-auth/logout`
- `GET /api/portal-auth/me`
- `POST /api/portal-auth/forgot-password`
- `POST /api/portal-auth/reset-password`

#### Portal User
- `GET /api/portal-user/profile`
- `PUT /api/portal-user/profile`
- `PUT /api/portal-user/password`
- `GET /api/portal-user/preferences`
- `PUT /api/portal-user/preferences`
- `GET /api/portal-user/saved-posts`
- `POST /api/portal-user/saved-posts/{postId}`
- `DELETE /api/portal-user/saved-posts/{postId}`
- `POST /api/portal-user/interactions`
- `GET /api/portal-user/recommendations`
- `GET /api/portal-user/home-feed`

### Recommendation algorithm (v1)
The personalized ranking combines:
- Explicit preferences (favorite categories/tags/authors)
- Behavior (views, saves, category/tag clicks, read time)
- Editorial factors (recency, trending by views/shares, featured/breaking)
- Control factors (already seen penalty + category diversity penalty)

Weights are centralized in:
- `app/Config/PortalRecommendation.php`

Optional lightweight cron for score warmup:
- `php spark portal:refresh-recommendations`

### New database assets
- Migration: `app/Database/Migrations/2026-01-01-000023_CreatePortalUserTables.php`
- SQL schema extension: `sql/schema.sql` (sections 25-34)
- Portal seed class: `app/Database/Seeds/PortalUserFeatureSeeder.php`
- SQL test dataset extension: `sql/test-data.sql`

### Portal test users
- `lector.a@netxus.com` / `Portal123!`
- `lector.b@netxus.com` / `Portal123!`
- `lector.c@netxus.com` / `Portal123!`
- `lector.d@netxus.com` / `Portal123!`
