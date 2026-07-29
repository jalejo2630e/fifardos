# Despliegue en Dokploy

FIFARDOS se empaqueta en **un solo contenedor** (nginx + php-fpm + supervisor) que además ejecuta el **scheduler** (recordatorios de torneos por email) y un **worker de colas**.

## Archivos

| Archivo | Rol |
|---|---|
| `Dockerfile` | Imagen de producción multi-stage (Composer → Vite → runtime PHP) |
| `.dockerignore` | Excluye node_modules, vendor, .env, etc. del contexto de build |
| `docker/nginx.conf` | Servidor web (raíz en `public/`, gzip, cache de assets) |
| `docker/php.ini` | Ajustes PHP + OPcache de producción |
| `docker/supervisord.conf` | Orquesta php-fpm, nginx, `schedule:work` y `queue:work` |
| `docker/entrypoint.sh` | Migraciones, cache de config/vistas, storage link |
| `docker-compose.yml` | Opción de despliegue tipo *Compose* + volumen persistente |
| `.env.production.example` | Variables de entorno de referencia |

## Opción A — Aplicación (Dockerfile)

1. En Dokploy crea una **Application** y apunta al repositorio.
2. **Build Type:** `Dockerfile` (ruta: `Dockerfile`).
3. **Puerto:** `80` (el contenedor escucha ahí; Traefik enruta tu dominio).
4. **Environment:** pega las variables de `.env.production.example` con tus valores reales.
5. **Volumen persistente** (recomendado): monta `/var/www/html/storage`
   (guarda logs, sesiones, archivos subidos y, si usas SQLite, la base).
6. Asigna tu **dominio** y activa HTTPS (Let's Encrypt).
7. **Deploy.**

## Opción B — Docker Compose

1. En Dokploy crea un servicio **Compose** y usa `docker-compose.yml`.
2. Define las variables en *Environment* (o vía `.env`).
3. Descomenta el servicio `db` (Postgres) si quieres base gestionada por el compose.
4. **Deploy.**

## Variables imprescindibles

- `APP_KEY` — genérala una vez: `php artisan key:generate --show` y pégala. **No la cambies** después (invalida sesiones).
- `APP_URL` — tu dominio con `https://` (afecta enlaces, canonical, OG y emails).
- `APP_ENV=production`, `APP_DEBUG=false`.
- **Base de datos:** lo recomendado es Postgres/MySQL. Para SQLite persistente usa
  `DB_CONNECTION=sqlite` y `DB_DATABASE=/var/www/html/storage/app/database.sqlite`
  (así queda dentro del volumen `storage`).
- **Email (recordatorios):** configura SMTP real (`MAIL_MAILER=smtp`, host, usuario, etc.).
- `GEMINI_API_KEY` — para el chatbot y la búsqueda semántica.

## Qué hace el arranque automáticamente

- Recrea la estructura de `storage/` (por si el volumen viene vacío).
- `php artisan migrate --force` (desactívalo con `AUTO_MIGRATE=false`).
- Cachea configuración y vistas. *(No cachea rutas: hay rutas con closures.)*
- Levanta nginx + php-fpm + scheduler + queue vía supervisor.

## Salud y logs

- **Healthcheck:** `GET /up` (endpoint de salud de Laravel).
- Todos los procesos escriben a **stdout/stderr**, visibles en los logs de Dokploy.

## Post-deploy útil

Ejecuta comandos con la consola de Dokploy o `docker exec`:

```bash
php artisan db:seed --force          # datos de ejemplo (opcional)
php artisan tournaments:send-reminders   # forzar envío de recordatorios
```

## Build local (opcional, para probar)

```bash
docker build -t fifardos .
docker run --rm -p 8080:80 --env-file .env fifardos
# abre http://localhost:8080
```
