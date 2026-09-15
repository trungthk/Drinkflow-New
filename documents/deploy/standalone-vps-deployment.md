# Hướng dẫn Triển khai DrinkFlow lên VPS (Cài đặt từng dịch vụ - Native Bare-Metal)

Tài liệu này hướng dẫn chi tiết cách cài đặt và cấu hình từng dịch vụ riêng biệt trực tiếp trên hệ điều hành **Ubuntu 22.04 / 24.04 LTS** mà không sử dụng Docker, nhằm tối ưu hóa 100% hiệu năng phần cứng cho hệ thống **DrinkFlow Enterprise**.

---

## 1. Danh sách Dịch vụ Cần Cài đặt

1. **Nginx Web Server** (Xử lý HTTPS, Static assets & Reverse Proxy WebSocket).
2. **PHP 8.3 & PHP-FPM** (Chạy ứng dụng Laravel 12 với đầy đủ extensions).
3. **MySQL 8.0/8.4** (Hệ quản trị cơ sở dữ liệu quan hệ tự host).
4. **Redis Server** (Bộ nhớ đệm Session, Cache và Queue Worker).
5. **Node.js 22 LTS & npm** (Xây dựng assets Vite & chạy Socket.IO Gateway).
6. **Supervisor** (Giám sát và duy trì tiến trình chạy ngầm Queue Worker và Socket.IO).
7. **Composer 2** (Trình quản lý thư viện PHP).
8. **Certbot** (Cấp và tự động gia hạn SSL Let's Encrypt).

---

## 2. Chuẩn bị VPS & Cài đặt Các Gói Hệ Thống Cơ Bản

Đăng nhập vào máy chủ bằng SSH với quyền `root` hoặc tài khoản có quyền `sudo`:

```bash
# Cập nhật kho gói APT (không tự động nâng cấp toàn bộ VPS đang hoạt động)
sudo apt update
sudo apt install -y software-properties-common curl wget git unzip htop ufw ca-certificates gnupg lsb-release

# Thiết lập Timezone sang Việt Nam
sudo timedatectl set-timezone Asia/Ho_Chi_Minh

# Tạo 2GB-4GB Swap file chống tràn RAM
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### 2.1. Nguyên tắc khi VPS đang chạy nhiều website

Không xem VPS là máy chủ mới nếu đã có website đang hoạt động. Trước khi cài đặt,
ghi lại các service, port và virtual host hiện có; không dừng, xóa hoặc thay thế cấu
hình của ứng dụng khác:

```bash
# Kiểm tra các service và port đang sử dụng
sudo systemctl --type=service --state=running
sudo ss -ltnp

# Sao lưu cấu hình Nginx nếu Nginx đã tồn tại
if [ -d /etc/nginx ]; then
    sudo cp -a /etc/nginx /etc/nginx.backup.$(date +%Y%m%d-%H%M%S)
fi
```

Nếu máy đang có lịch bảo trì riêng, không chạy `apt upgrade` trong giờ cao điểm vì
việc nâng cấp có thể làm restart các service hiện hữu. Có thể tách bước nâng cấp ra
khỏi lần triển khai DrinkFlow và thực hiện sau khi đã kiểm tra kế hoạch downtime. Khi
cần nâng cấp, chạy riêng trong maintenance window:

```bash
sudo apt update
sudo apt upgrade -y
```

Không chạy `ufw reset` hoặc mở các port tùy tiện. Chỉ bổ sung rule còn thiếu và giữ
nguyên các rule của website khác:

```bash
sudo ufw status numbered
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

MySQL, Redis và Socket.IO phải chỉ lắng nghe trên `127.0.0.1`; không mở các port
3306, 6379 hoặc 3001 ra Internet.

---

## 3. Cài đặt & Cấu hình Từng Dịch vụ

### 3.1. Cài đặt Nginx Web Server

Nếu Nginx đã được cài đặt để phục vụ các website khác, chỉ kiểm tra trạng thái và
không cần cài lại hoặc khởi động lại service:

```bash
if command -v nginx >/dev/null 2>&1; then
    nginx -v
    sudo systemctl status nginx --no-pager
else
    sudo apt install -y nginx
    sudo systemctl enable nginx
    sudo systemctl start nginx
fi
```

---

### 3.2. Cài đặt PHP 8.3 & Các PHP Extensions

Thêm kho lưu trữ PPA Ondřej Surý để cài đặt PHP 8.3 chuẩn:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Cài đặt PHP 8.3 FPM, CLI và các extension thiết yếu cho Laravel
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-sqlite3 \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-gd php8.3-bcmath php8.3-intl php8.3-redis \
    php8.3-opcache php8.3-readline

# Kiểm tra phiên bản PHP
php -v
```

#### Tối ưu hóa `php.ini` cho Production:
Mở file `/etc/php/8.3/fpm/php.ini`:

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Điều chỉnh các thông số sau:

```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 120
date.timezone = Asia/Ho_Chi_Minh

# Bật OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
```

Khởi động lại PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
sudo systemctl enable php8.3-fpm
```

---

### 3.3. Cài đặt & Khởi tạo MySQL tự host

Phần này cài MySQL trực tiếp trên VPS, không sử dụng dịch vụ cơ sở dữ liệu managed hoặc
kết nối từ trình duyệt. Gói `mysql-server` của Ubuntu cung cấp MySQL 8.x phù hợp với
Ubuntu 22.04/24.04:

```bash
sudo apt update
sudo apt install -y mysql-server

sudo systemctl enable mysql
sudo systemctl start mysql
sudo systemctl status mysql --no-pager
```

Chạy trình cấu hình bảo mật và chọn mật khẩu root theo yêu cầu của hệ thống:

```bash
sudo mysql_secure_installation
```

Để tránh mở MySQL ra Internet, giữ `bind-address` ở loopback. Kiểm tra file
`/etc/mysql/mysql.conf.d/mysqld.cnf` và bảo đảm có các thiết lập sau:

```ini
[mysqld]
bind-address = 127.0.0.1
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

Khởi động lại MySQL sau khi thay đổi cấu hình:

```bash
sudo systemctl restart mysql
```

#### Tạo Database & User riêng cho DrinkFlow

Đăng nhập MySQL bằng tài khoản quản trị:

```bash
sudo mysql
```

Trong dấu nhắc lệnh `mysql`, thay `MAT_KHAU_DATABASE_THAT_MANH` bằng một mật khẩu
được tạo riêng và lưu trong trình quản lý secrets:

```sql
CREATE DATABASE drinkflow_prod
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'drinkflow_user'@'127.0.0.1'
    IDENTIFIED BY 'MAT_KHAU_DATABASE_THAT_MANH';

GRANT ALL PRIVILEGES ON drinkflow_prod.* TO 'drinkflow_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

Tài khoản được giới hạn đúng vào `127.0.0.1`, khớp với `DB_HOST` bên dưới. Không cấp user
với host `%` và không dùng tài khoản `root` cho ứng dụng. Kiểm tra kết nối bằng:

```bash
mysql --host=127.0.0.1 --port=3306 \
    --user=drinkflow_user --password drinkflow_prod
```

Nếu cần truy cập MySQL từ máy quản trị, dùng SSH tunnel thay vì mở cổng 3306 trên Internet.

---

### 3.4. Cài đặt Redis Server (Cache & Queue)

```bash
sudo apt install -y redis-server

# Mở file cấu hình redis
sudo nano /etc/redis/redis.conf
```

Tìm dòng `supervised no` đổi thành `supervised systemd`:

```ini
supervised systemd
maxmemory 256mb
maxmemory-policy allkeys-lru
```

Khởi động lại Redis:

```bash
sudo systemctl restart redis-server
sudo systemctl enable redis-server

# Kiểm tra kết nối
redis-cli ping
# Kết quả trả về: PONG
```

---

### 3.5. Cài đặt Composer 2 & Node.js 22 LTS

```bash
# Cài đặt Composer toàn hệ thống
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Cài đặt Node.js 22 LTS qua NodeSource
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Kiểm tra phiên bản
composer --version
node -v
npm -v
```

---

## 4. Triển khai Mã Nguồn DrinkFlow

### 4.1. Clone Source Code & Cấp Quyền

```bash
sudo mkdir -p /var/www/drinkflow
sudo chown -R $USER:www-data /var/www/drinkflow
cd /var/www/drinkflow

git clone https://github.com/your-username/Drinkflow-New.git .
```

### 4.2. Cài đặt PHP Dependencies & Build Frontend Assets

```bash
cd /var/www/drinkflow/src

# 1. Cài đặt PHP Packages cho Production
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Cài đặt Node packages & Build Vite CSS/JS
npm install
npm run build

# 3. Cài đặt dependencies cho Realtime Gateway
cd /var/www/drinkflow/realtime
npm install --production
```

### 4.3. Cấu hình file `.env` của Laravel

Quay lại thư mục `src/`:

```bash
cd /var/www/drinkflow/src
cp .env.example .env
nano .env
```

Cấu hình các thông số:

```ini
APP_NAME=DrinkFlow
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://drinkflow.yourcompany.com
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_LOCALE=vi

# Kết nối MySQL tự host, chỉ lắng nghe trên máy VPS
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drinkflow_prod
DB_USERNAME=drinkflow_user
DB_PASSWORD=MAT_KHAU_DATABASE_THAT_MANH

# Session & Cache qua Redis (hoặc database)
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Realtime Socket.IO
REALTIME_URL=http://127.0.0.1:3001
REALTIME_PUBLIC_URL=https://drinkflow.yourcompany.com
REALTIME_INTERNAL_SECRET=DrinkFlowSecret_Prod_Key_99999
SOCKET_TOKEN_SECRET=SocketTokenSecret_Prod_Key_88888
CORS_ORIGIN=https://drinkflow.yourcompany.com

# Google OAuth
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=https://drinkflow.yourcompany.com/auth/google/callback
```

### 4.4. Sinh App Key, Chạy Migration & Phân Quyền Thư mục

```bash
cd /var/www/drinkflow/src

# Sinh Application Encryption Key
php artisan key:generate --force

# Chạy Migration và Seed CSDL
php artisan migrate --force
php artisan db:seed --force

# Phân quyền chuẩn cho Web Server
sudo chown -R www-data:www-data /var/www/drinkflow/src/storage /var/www/drinkflow/src/bootstrap/cache
sudo chmod -R 775 /var/www/drinkflow/src/storage /var/www/drinkflow/src/bootstrap/cache

# Tạo storage link nếu cần
php artisan storage:link

# Tối ưu hóa cache Production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Cấu hình Nginx Virtual Host & SSL

### 5.1. Tạo Server Block Nginx

Tạo file `/etc/nginx/sites-available/drinkflow`:

```bash
sudo nano /etc/nginx/sites-available/drinkflow
```

Dán cấu hình chi tiết dưới đây:

```nginx
server {
    listen 80;
    server_name drinkflow.yourcompany.com;
    root /var/www/drinkflow/src/public;

    index index.php index.html;
    charset utf-8;
    client_max_body_size 50M;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript image/svg+xml;

    # 1. Socket.IO Realtime Proxy
    location /socket.io/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400s;
        proxy_send_timeout 86400s;
    }

    # 2. Main Laravel Routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # 3. PHP-FPM FastCGI Handler
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }

    # 4. Static Build Assets Cache
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2|woff|ttf|svg|webp)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        access_log off;
    }

    # Block hidden files (.env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Kích hoạt site:

```bash
sudo ln -sf /etc/nginx/sites-available/drinkflow /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Không xóa `/etc/nginx/sites-enabled/default` hoặc các symlink của website khác. Nếu
Nginx báo lỗi, không reload và không sửa trực tiếp các site đang hoạt động; khôi phục
file DrinkFlow vừa thay đổi rồi chạy lại `sudo nginx -t`. `reload` được dùng thay cho
`restart` để Nginx nạp cấu hình mới mà không ngắt các kết nối hiện tại.

### 5.2. Cài đặt SSL Let's Encrypt bằng Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d drinkflow.yourcompany.com
```

---

## 6. Cấu hình Supervisor Quản lý Tiến trình Chạy ngầm

Supervisor sẽ tự động duy trì và khởi động lại **Queue Worker** và **Socket.IO Realtime Gateway** khi máy chủ khởi động lại hoặc khi tiến trình gặp sự cố.

### 6.1. Cài đặt Supervisor

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 6.2. Cấu hình Laravel Queue Worker

Tạo file cấu hình `/etc/supervisor/conf.d/drinkflow-worker.conf`:

```bash
sudo nano /etc/supervisor/conf.d/drinkflow-worker.conf
```

Nội dung:

```ini
[program:drinkflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/drinkflow/src/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/drinkflow/src/storage/logs/worker.log
stopwaitsecs=3600
```

### 6.3. Cấu hình Node.js Socket.IO Realtime Server

Tạo file cấu hình `/etc/supervisor/conf.d/drinkflow-realtime.conf`:

```bash
sudo nano /etc/supervisor/conf.d/drinkflow-realtime.conf
```

Nội dung:

```ini
[program:drinkflow-realtime]
directory=/var/www/drinkflow/realtime
command=node server.js
autostart=true
autorestart=true
user=www-data
environment=NODE_ENV="production",PORT="3001"
redirect_stderr=true
stdout_logfile=/var/www/drinkflow/src/storage/logs/realtime.log
```

### 6.4. Nạp và Khởi chạy Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

---

## 7. Cấu hình Cronjob cho Laravel Task Scheduler

Mở crontab của user `www-data`:

```bash
sudo crontab -u www-data -e
```

Thêm dòng sau vào cuối:

```cron
* * * * * php /var/www/drinkflow/src/artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Script Cập nhật Mã Nguồn Định kỳ (`deploy-native.sh`)

Tạo file `/var/www/drinkflow/deploy-native.sh`:

```bash
nano /var/www/drinkflow/deploy-native.sh
```

Nội dung script:

```bash
#!/usr/bin/env bash
set -e

cd /var/www/drinkflow

echo "🚀 [1/7] Kéo mã nguồn mới nhất..."
git pull origin main

echo "📦 [2/7] Cài đặt Composer Dependencies..."
cd src
composer install --no-dev --optimize-autoloader --no-interaction

echo "🎨 [3/7] Build Assets Vite..."
npm install
npm run build

echo "🗄️ [4/7] Chạy Database Migrations..."
php artisan migrate --force

echo "⚡ [5/7] Làm mới Cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔄 [6/7] Khởi động lại Worker & Realtime..."
sudo supervisorctl restart all
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "✅ [7/7] Cập nhật thành công!"
```

Cấp quyền:

```bash
chmod +x /var/www/drinkflow/deploy-native.sh
```

Mỗi khi muốn deploy code mới, chỉ cần chạy:

```bash
./deploy-native.sh
```
