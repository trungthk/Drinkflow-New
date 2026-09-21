# DrinkFlow Deployment Guide — Docker Compose

## 1. Mục tiêu

Tài liệu này hướng dẫn triển khai DrinkFlow ở hai môi trường:

- **Local Development**
- **VPS Production**

Stack:

- Laravel 12
- PHP 8.3+
- Blade / TailwindCSS / Vite
- Nginx
- Redis
- Laravel Queue
- Laravel Scheduler
- Socket.IO Gateway
- Supabase PostgreSQL

Kiến trúc:

```text
Browser
   │
   ▼
Nginx
   │
   ├──────────────► Laravel PHP-FPM
   │                     │
   │                     ├── Supabase PostgreSQL
   │                     └── Redis
   │
   └──────────────► Socket.IO Gateway

Queue Worker ────────────► Redis
Scheduler ───────────────► Laravel / Database
```

Nguyên tắc:

```text
Laravel = Source of Truth
Supabase = PostgreSQL Database
Redis = Cache / Session / Queue
Socket.IO = Realtime Transport
Nginx = Public Gateway
Docker Compose = Deployment Orchestration
```

Browser không truy cập trực tiếp Supabase.

---

## 2. Docker Services

Các service chính:

```text
app
nginx
redis
queue
scheduler
socket
```

Optional local:

```text
mailpit
```

Không cần PostgreSQL container nếu sử dụng Supabase PostgreSQL remote.

---

## 3. Folder Structure

```text
drinkflow/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── php.ini
│   │   └── opcache.ini
│   └── socket/
│       └── Dockerfile
├── socket/
│   ├── package.json
│   ├── package-lock.json
│   └── server.js
├── .env
├── .env.example
├── .env.production
├── docker-compose.yml
├── docker-compose.prod.yml
├── composer.json
├── package.json
└── vite.config.js
```

---

## 4. PHP Dockerfile

`docker/php/Dockerfile`

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

CMD ["php-fpm"]
```

Local development có thể sử dụng Dockerfile/target riêng để:

- Cài dev dependencies.
- Mount source.
- Cài Xdebug nếu cần.

---

## 5. Nginx Config

`docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name _;

    root /var/www/html/public;
    index index.php;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ^~ /socket.io/ {
        proxy_pass http://socket:6001;

        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass app:9000;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Không expose:

- `.env`
- `storage/logs`
- source/config nhạy cảm

---

## 6. Socket.IO Dockerfile

`docker/socket/Dockerfile`

```dockerfile
FROM node:22-alpine

WORKDIR /app

COPY socket/package*.json ./
RUN npm ci --omit=dev

COPY socket/ .

CMD ["node", "server.js"]
```

Socket Gateway phải:

- Verify signed short-lived token do Laravel cấp.
- Không query Supabase trực tiếp.
- Không update database.
- Không chứa business logic.
- Không tin channel/room/user ID do browser tự claim.
- Chỉ join channel đã được Laravel authorize.

---

## 7. Docker Compose — Local

`docker-compose.yml`

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    env_file:
      - .env
    depends_on:
      redis:
        condition: service_healthy
    networks:
      - drinkflow

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app
      - socket
    networks:
      - drinkflow

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 5
    networks:
      - drinkflow

  queue:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: php artisan queue:work --sleep=3 --tries=3 --timeout=120
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    env_file:
      - .env
    depends_on:
      redis:
        condition: service_healthy
    networks:
      - drinkflow

  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: php artisan schedule:work
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    env_file:
      - .env
    depends_on:
      - app
    networks:
      - drinkflow

  socket:
    build:
      context: .
      dockerfile: docker/socket/Dockerfile
    env_file:
      - .env
    networks:
      - drinkflow

volumes:
  redis-data:

networks:
  drinkflow:
    driver: bridge
```

Local URL:

```text
http://localhost:8080
```

Socket.IO đi qua Nginx:

```text
http://localhost:8080/socket.io/
```

---

## 8. Local `.env`

```env
APP_NAME=DrinkFlow
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=your-supabase-host
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=your-user
DB_PASSWORD=your-password
DB_SSLMODE=require

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
GOOGLE_ALLOWED_DOMAINS=company.com

SOCKET_INTERNAL_URL=http://socket:6001
SOCKET_SIGNING_SECRET=

VITE_APP_URL=http://localhost:8080
VITE_SOCKET_URL=http://localhost:8080
```

Không commit `.env`.

---

## 9. Local Startup

```bash
cp .env.example .env

docker compose build
docker compose up -d

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
```

Build frontend:

```bash
npm ci
npm run build
```

Khi develop có thể chạy:

```bash
npm run dev
```

---

## 10. Production Strategy

Production nên ưu tiên **immutable Docker image** thay vì mount source từ host.

Mục tiêu:

- Build image ở CI/local.
- Push registry.
- VPS chỉ pull image.
- Laravel `app`, `queue`, `scheduler` dùng cùng một application image.
- Socket.IO dùng image riêng.
- Redis, PHP-FPM và port Socket không public Internet.

---

## 11. Production Compose

`docker-compose.prod.yml`

```yaml
services:
  app:
    image: ghcr.io/company/drinkflow:${APP_VERSION}
    restart: unless-stopped
    env_file:
      - .env.production
    networks:
      - drinkflow

  nginx:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - ./public:/var/www/html/public:ro
    depends_on:
      - app
      - socket
    networks:
      - drinkflow

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: redis-server --appendonly yes
    volumes:
      - redis-data:/data
    networks:
      - drinkflow

  queue:
    image: ghcr.io/company/drinkflow:${APP_VERSION}
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --timeout=120
    env_file:
      - .env.production
    depends_on:
      - redis
    networks:
      - drinkflow

  scheduler:
    image: ghcr.io/company/drinkflow:${APP_VERSION}
    restart: unless-stopped
    command: php artisan schedule:work
    env_file:
      - .env.production
    depends_on:
      - app
    networks:
      - drinkflow

  socket:
    image: ghcr.io/company/drinkflow-socket:${APP_VERSION}
    restart: unless-stopped
    env_file:
      - .env.production
    networks:
      - drinkflow

volumes:
  redis-data:

networks:
  drinkflow:
    driver: bridge
```

---

## 12. VPS Recommendation

Khuyến nghị thực tế:

```text
Ubuntu 22.04 / 24.04
2 CPU
2 GB RAM
20+ GB SSD
```

VPS 1 GB RAM vẫn có thể chạy hệ thống nhỏ, nhưng không nên build Docker/Vite trực tiếp trên VPS.

VPS nhỏ nên:

```text
CI build
→ Registry
→ VPS pull
```

---

## 13. Production `.env`

```env
APP_NAME=DrinkFlow
APP_ENV=production
APP_DEBUG=false
APP_URL=https://drinkflow.example.com

DB_CONNECTION=pgsql
DB_HOST=your-supabase-host
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=your-user
DB_PASSWORD=your-password
DB_SSLMODE=require

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PORT=6379

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://drinkflow.example.com/auth/google/callback
GOOGLE_ALLOWED_DOMAINS=company.com

SOCKET_INTERNAL_URL=http://socket:6001
SOCKET_SIGNING_SECRET=

VITE_APP_URL=https://drinkflow.example.com
VITE_SOCKET_URL=https://drinkflow.example.com
```

Không bao giờ đặt `SOCKET_SIGNING_SECRET` hoặc backend secret trong `VITE_*`.

---

## 14. First VPS Deployment

```bash
mkdir -p /var/www/drinkflow
cd /var/www/drinkflow

git clone <repository> .
```

Tạo `.env.production`.

```bash
export APP_VERSION=1.0.0

docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

Migration:

```bash
docker compose -f docker-compose.prod.yml exec app \
  php artisan migrate --force
```

Optimize:

```bash
docker compose -f docker-compose.prod.yml exec app \
  php artisan optimize
```

---

## 15. Laravel Optimization

Production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Hoặc:

```bash
php artisan optimize
```

Sau khi thay `.env`:

```bash
php artisan optimize:clear
php artisan optimize
```

---

## 16. Queue Worker

```bash
php artisan queue:work \
  --sleep=3 \
  --tries=3 \
  --timeout=120
```

Sau deploy:

```bash
docker compose -f docker-compose.prod.yml restart queue
```

Job cần idempotent nếu có khả năng retry.

---

## 17. Scheduler

Dùng dedicated container:

```bash
php artisan schedule:work
```

Nếu đã dùng scheduler container, không chạy thêm host cron `schedule:run`.

Chỉ chọn một scheduler strategy.

---

## 18. HTTPS / Cloudflare

Recommended:

```text
Internet
   │
   ▼
Cloudflare
   │
   ▼
VPS Nginx
```

SSL mode:

```text
Full
hoặc
Full (Strict)
```

Không dùng Flexible.

Google OAuth callback production phải dùng HTTPS.

---

## 19. Firewall

Chỉ public:

```text
22
80
443
```

Không public:

```text
6379
9000
6001
```

Socket.IO đi qua Nginx `/socket.io/`.

---

## 20. Health Check

Nên có:

```text
GET /health
```

Response tối giản:

```json
{
  "status": "ok"
}
```

Internal health có thể kiểm tra:

- Database.
- Redis.
- Queue.
- Socket.IO.

Public health endpoint không expose infrastructure detail hoặc secret.

---

## 21. Docker Healthcheck

Redis:

```yaml
healthcheck:
  test: ["CMD", "redis-cli", "ping"]
  interval: 10s
  timeout: 3s
  retries: 5
```

App nên có HTTP/application healthcheck phù hợp với image thực tế.

---

## 22. Logging

Docker log rotation:

```yaml
logging:
  driver: json-file
  options:
    max-size: "10m"
    max-file: "5"
```

Không log:

- Google OAuth token.
- Database password.
- Chatwork token.
- Slack webhook.
- Telegram bot token.
- Device trusted token.
- Socket signing secret.

---

## 23. Persistent Storage

Nếu upload local:

```text
storage/app/public
```

phải dùng persistent volume/bind mount.

Production nên ưu tiên S3-compatible storage cho asset cần persistence.

Không lưu upload quan trọng chỉ trong ephemeral container filesystem.

---

## 24. Redis

Nếu Redis dùng cho:

- Session.
- Queue.
- Cache.

nên dùng:

```text
appendonly yes
```

và volume persistence.

---

## 25. Supabase PostgreSQL

Laravel kết nối trực tiếp:

```env
DB_CONNECTION=pgsql
DB_SSLMODE=require
```

Không sử dụng từ browser:

```text
supabase-js
Supabase REST
PostgREST
```

Cần chọn Supabase pooler/connection mode phù hợp với workload Laravel.

---

## 26. Migration Production

Chỉ dùng:

```bash
php artisan migrate --force
```

Không dùng:

```bash
php artisan migrate:fresh
```

production.

Trước migration lớn:

1. Backup.
2. Test staging.
3. Review destructive operation.
4. Có rollback plan.

---

## 27. Backup

Backup:

- Supabase PostgreSQL.
- `.env.production`.
- Upload local nếu có.
- Runtime configuration quan trọng.

Không đặt backup trong public directory.

Recommended:

```text
/var/backups/drinkflow/
```

hoặc remote object storage.

---

## 28. Deployment Update Flow

```text
Git Push
   │
   ▼
CI Test
   │
   ▼
Build Images
   │
   ▼
Push Registry
   │
   ▼
VPS Pull
   │
   ▼
Migration
   │
   ▼
docker compose up -d
   │
   ▼
Laravel optimize
   │
   ▼
Queue restart
   │
   ▼
Health check
```

Commands:

```bash
export APP_VERSION=1.2.0

docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d

docker compose -f docker-compose.prod.yml exec app \
  php artisan migrate --force

docker compose -f docker-compose.prod.yml exec app \
  php artisan optimize

docker compose -f docker-compose.prod.yml restart queue
```

---

## 29. Maintenance Mode

Chỉ cần khi deploy/migration không backward-compatible.

```bash
docker compose exec app php artisan down
```

Sau deploy:

```bash
docker compose exec app php artisan up
```

---

## 30. Rollback

Dùng versioned image:

```text
drinkflow:1.2.0
drinkflow:1.1.9
```

Rollback bằng cách đổi `APP_VERSION` về version cũ và:

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

Không rollback database mù quáng nếu production đã ghi dữ liệu theo schema mới.

---

## 31. Image Versioning

Không chỉ sử dụng:

```text
latest
```

Recommended:

```text
semantic version
+
git commit SHA
```

Ví dụ:

```text
1.2.0
1.2.0-a13c9fe
```

---

## 32. Environment Separation

Tách:

```text
local
staging
production
```

Không dùng chung:

- Database.
- Redis.
- Google OAuth callback.
- Notification credential.
- Socket secret.

---

## 33. Google OAuth Configuration

Local:

```text
http://localhost:8080/auth/google/callback
```

Production:

```text
https://drinkflow.example.com/auth/google/callback
```

Laravel luôn verify:

```text
email_verified
company domain
provider identity
```

server-side.

---

## 34. Security Baseline

Khuyến nghị VPS:

- SSH key.
- Firewall.
- Không expose Docker daemon.
- Không expose Redis/PHP-FPM/Socket port.
- Security updates.
- Backup.
- Log rotation.
- Disk monitoring.
- Fail2ban optional.
- Không chạy application bằng root nếu không cần.

---

## 35. Disk Monitoring

```bash
df -h
docker system df
```

Cleanup an toàn hơn:

```bash
docker image prune
docker builder prune
```

Không tùy tiện chạy:

```bash
docker system prune -a --volumes
```

trên production.

---

## 36. Useful Commands

Status:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f queue
docker compose logs -f scheduler
docker compose logs -f socket
```

Laravel:

```bash
docker compose exec app php artisan about
```

Failed jobs:

```bash
docker compose exec app php artisan queue:failed
```

Restart:

```bash
docker compose restart app queue scheduler socket
```

---

## 37. Local Workflow

```text
docker compose up -d
        │
        ▼
Edit source
        │
        ▼
Laravel source mount
        │
        ▼
npm run dev
        │
        ▼
http://localhost:8080
```

Nếu dùng Windows + WSL và mount chậm, nên đặt source trong Linux filesystem.

---

## 38. CI/CD Production Flow

```text
Developer
   │
   ▼
Git Push
   │
   ▼
CI Tests
   │
   ▼
Build Laravel Image
Build Socket Image
   │
   ▼
Push Registry
   │
   ▼
VPS Pull
   │
   ▼
Deploy
   │
   ▼
Migration
   │
   ▼
Health Check
```

Registry có thể là:

- GitHub Container Registry.
- GitLab Container Registry.
- Docker Hub.
- Private Registry.

---

## 39. Container Responsibilities

### `app`

```text
PHP-FPM
Laravel HTTP
```

### `nginx`

```text
Public gateway
Static assets
FastCGI
Socket.IO reverse proxy
```

### `queue`

```text
Laravel queue worker
```

### `scheduler`

```text
Laravel scheduler
```

### `redis`

```text
Cache
Session
Queue
```

### `socket`

```text
Socket.IO realtime transport
```

Node Socket container không được trở thành business backend thứ hai.

---

## 40. Secret Management

Minimum:

```text
.env.production
chmod 600
```

Tốt hơn:

- CI secrets.
- Docker secrets nếu phù hợp.
- External secret manager.

Không bake production secrets vào Docker image.

---

## 41. File Permissions

Laravel cần write access:

```text
storage
bootstrap/cache
```

Không dùng:

```bash
chmod -R 777
```

production.

Dùng owner/group phù hợp với runtime container, thường là `www-data`.

---

## 42. Production Checklist

Trước deploy:

- `.env.production` đúng.
- `APP_DEBUG=false`.
- `APP_KEY` tồn tại.
- Supabase connection OK.
- Redis OK.
- Google callback đúng.
- Company domains đúng.
- Socket signing secret đúng.
- Notification credentials đúng.
- Migration đã review.
- Backup sẵn sàng.
- Frontend assets build thành công.

Sau deploy:

- `/health` OK.
- Admin login OK.
- Google authentication OK.
- Global User resolve OK.
- Join Room OK.
- Trusted Device OK.
- Order OK.
- Single Active Order OK.
- Queue OK.
- Scheduler OK.
- Socket.IO realtime OK.
- Không leak secret.
- Không cross-room leakage.
- HTTPS OK.

---

## 43. Deployment Summary

```text
Internet
   │
   ▼
Cloudflare / TLS
   │
   ▼
Nginx
   │
   ├────────────► Laravel App
   │                  │
   │                  ├── Supabase PostgreSQL
   │                  └── Redis
   │
   └────────────► Socket.IO

Queue ───────────────► Redis
Scheduler ───────────► Laravel / Database
```

Final rule:

```text
Local
→ source mount + debug + dev assets

Production
→ immutable/versioned images + HTTPS + internal services + healthcheck + backup
```
