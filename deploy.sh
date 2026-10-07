#!/bin/bash
set -e

echo "=== Installing dependencies ==="
sudo apt update
sudo apt install -y php php-sqlite3 php-mbstring php-xml php-curl php-zip php-fpm nginx unzip

# Install Composer
if ! command -v composer &> /dev/null; then
    echo "=== Installing Composer ==="
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# Install Node.js via NodeSource
if ! command -v node &> /dev/null; then
    echo "=== Installing Node.js ==="
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt install -y nodejs
fi

echo "=== Setting up Sorted ==="
cd /var/www/sorted

# Take ownership so we can write files (will give back to www-data at the end)
sudo chown -R $(whoami):$(whoami) .

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Update .env for production
sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i "s|APP_URL=.*|APP_URL=http://$(hostname -I | awk '{print $1}'):8080|" .env

# Install and build frontend
npm install
npm run build

# Database
touch database/database.sqlite
php artisan migrate --force

# Permissions - give www-data ownership, keep group writable for deploys
sudo chown -R www-data:www-data /var/www/sorted
sudo chmod -R 775 storage bootstrap/cache database

echo "=== Configuring Nginx ==="
PHP_FPM_SOCK=$(find /run/php/ -name "*.sock" 2>/dev/null | head -1)
if [ -z "$PHP_FPM_SOCK" ]; then
    PHP_FPM_SOCK="/run/php/php-fpm.sock"
fi

sudo tee /etc/nginx/sites-available/sorted > /dev/null <<NGINX
server {
    listen 8080;
    server_name _;
    root /var/www/sorted/public;
    index index.php;

    # Open pages check this every few seconds; served here so the checks never start PHP.
    location = /sync.txt {
        add_header Cache-Control "no-store";
        log_not_found off;
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

sudo ln -sf /etc/nginx/sites-available/sorted /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo systemctl restart php*-fpm nginx

echo "=== Setting up the scheduler ==="
# Laravel's scheduler: checks every minute; tasks:generate creates each day's chores at midnight.
sudo tee /etc/cron.d/sorted > /dev/null <<CRON
* * * * * www-data cd /var/www/sorted && php artisan schedule:run > /dev/null 2>&1
CRON

IP=$(hostname -I | awk '{print $1}')
echo ""
echo "=== Done! ==="
echo "Sorted is running at http://${IP}:8080"
echo "Add it to Home Assistant as an iframe panel or Webpage card."
