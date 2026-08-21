#!/usr/bin/env bash
#
# SULO — Raspberry Pi Setup Script
# Installs and configures the local backend server
#
# Usage:
#   chmod +x setup-rpi.sh
#   sudo ./setup-rpi.sh
#
# Target: Raspberry Pi 4/5 running Raspberry Pi OS (Debian-based)
#

set -euo pipefail

echo "═══════════════════════════════════════════════"
echo "  SULO — Local Server Setup"
echo "  Target: Raspberry Pi (Raspberry Pi OS)"
echo "═══════════════════════════════════════════════"
echo ""

# ─── Configuration ───
SULO_USER="${SULO_USER:-sulo}"
SULO_DB="sulo"
SULO_DB_USER="sulo_user"
SULO_DB_PASS="sulo_secure_password"
SULO_DIR="/opt/sulo"
SERVER_IP="192.168.4.1"
SERVER_PORT="8000"

# ─── Step 1: System packages ───
echo "[1/8] Updating system packages..."
apt-get update -qq
apt-get install -y -qq \
    curl wget git unzip \
    php php-cli php-mbstring php-xml php-curl php-pgsql php-zip php-bcmath \
    postgresql postgresql-contrib \
    nginx \
    ufw

# ─── Step 2: Install Composer ───
echo "[2/8] Installing Composer..."
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
composer --version

# ─── Step 3: Setup PostgreSQL ───
echo "[3/8] Configuring PostgreSQL..."
systemctl enable postgresql
systemctl start postgresql

# Create database and user
sudo -u postgres psql -c "CREATE USER ${SULO_DB_USER} WITH PASSWORD '${SULO_DB_PASS}';" 2>/dev/null || true
sudo -u postgres psql -c "CREATE DATABASE ${SULO_DB} OWNER ${SULO_DB_USER};" 2>/dev/null || true
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${SULO_DB} TO ${SULO_DB_USER};" 2>/dev/null || true

echo "  Database '${SULO_DB}' ready."

# ─── Step 4: Deploy application ───
echo "[4/8] Deploying SULO application..."
mkdir -p "${SULO_DIR}"
cp -r backend/* "${SULO_DIR}/"

cd "${SULO_DIR}"

# Copy .env
cp .env.example .env

# Generate APP_KEY
php artisan key:generate --force

# Update .env with actual values
sed -i "s|DB_HOST=127.0.0.1|DB_HOST=127.0.0.1|" .env
sed -i "s|DB_DATABASE=sulo|DB_DATABASE=${SULO_DB}|" .env
sed -i "s|DB_USERNAME=sulo_user|DB_USERNAME=${SULO_DB_USER}|" .env
sed -i "s|DB_PASSWORD=sulo_secure_password|DB_PASSWORD=${SULO_DB_PASS}|" .env
sed -i "s|APP_URL=http://192.168.4.1:8000|APP_URL=http://${SERVER_IP}:${SERVER_PORT}|" .env

# ─── Step 5: Install dependencies & run migrations ───
echo "[5/8] Installing Laravel dependencies..."
composer install --no-dev --optimize-autoloader

# Download Chart.js for offline dashboard
mkdir -p ${SULO_DIR}/public/js
curl -sL "https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" -o ${SULO_DIR}/public/js/chart.umd.min.js
echo "  Chart.js downloaded for offline dashboard."

echo "[6/8] Running database migrations..."
php artisan migrate --force

# Seed default status row
php artisan tinker --execute="
App\Models\DigesterStatus::create([
    'status_id' => 1,
    'reading_id' => 1,
    'valve_status' => 'closed',
    'overall_status' => 'Normal',
    'updated_at' => now(),
]);
" 2>/dev/null || true

# ─── Step 7: Configure Nginx ───
echo "[7/8] Configuring Nginx..."

cat > /etc/nginx/sites-available/sulo <<NGINX
server {
    listen 8000;
    server_name ${SERVER_IP} _;

    root ${SULO_DIR}/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 60;
    }

    location ~ /\.ht {
        deny all;
    }

    access_log /var/log/nginx/sulo-access.log;
    error_log  /var/log/nginx/sulo-error.log;
}
NGINX

ln -sf /etc/nginx/sites-available/sulo /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
systemctl enable nginx

# ─── Step 8: Firewall ───
echo "[8/8] Configuring firewall (UFW)..."
ufw allow 8000/tcp 2>/dev/null || true   # SULO Dashboard
ufw allow 22/tcp 2>/dev/null || true     # SSH

# ─── Set static IP (optional, info only) ───
echo ""
echo "═══════════════════════════════════════════════"
echo "  ✅ SULO Setup Complete!"
echo "═══════════════════════════════════════════════"
echo ""
echo "  Dashboard:    http://${SERVER_IP}:${SERVER_PORT}"
echo "  Database:     PostgreSQL (local)"
echo "  ESP32 POST:   http://${SERVER_IP}:${SERVER_PORT}/api/readings"
echo ""
echo "  Services:"
echo "    systemctl status nginx"
echo "    systemctl status postgresql"
echo ""
echo "  IMPORTANT: Set a static IP on this Pi:"
echo "    sudo nano /etc/dhcpcd.conf"
echo "    interface eth0  (or wlan0)"
echo "    static ip_address=${SERVER_IP}/24"
echo ""
echo "  ESP32 config.h should point to:"
echo "    #define SERVER_HOST \"${SERVER_IP}\""
echo "    #define SERVER_PORT ${SERVER_PORT}"
echo ""
