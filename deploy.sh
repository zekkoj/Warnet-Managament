#!/bin/bash

###############################################################################
# WARNET MANAGEMENT SYSTEM - DEPLOYMENT SCRIPT
# Description: Automated deployment script for production server
# Usage: ./deploy.sh
###############################################################################

set -e

echo "========================================="
echo "  WARNET MS - DEPLOYMENT SCRIPT"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/warnet"
BACKUP_DIR="/var/backups/warnet"
REPO_URL="https://github.com/yourusername/warnet-ms.git"

echo -e "${GREEN}[1/10] Pulling latest changes...${NC}"
cd $APP_DIR
git pull origin main

echo -e "${GREEN}[2/10] Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

echo -e "${GREEN}[3/10] Installing NPM dependencies...${NC}"
npm install --production

echo -e "${GREEN}[4/10] Building assets...${NC}"
npm run build

echo -e "${GREEN}[5/10] Backing up database...${NC}"
mkdir -p $BACKUP_DIR
mysqldump -u warnet_user -p warnet_db > "$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"

echo -e "${GREEN}[6/10] Running migrations...${NC}"
php artisan migrate --force

echo -e "${GREEN}[7/10] Clearing and caching...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo -e "${GREEN}[8/10] Optimizing...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}[9/10] Setting permissions...${NC}"
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR/storage
chmod -R 755 $APP_DIR/bootstrap/cache

echo -e "${GREEN}[10/10] Restarting services...${NC}"
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}  DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Visit: https://yourdomain.com"
