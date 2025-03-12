#!/bin/bash

echo "Starting Nextcloud repair and file scan..."

# Ensure the script is run with proper permissions
if [ "$(id -u)" != "0" ]; then
   echo "This script must be run as root or with sudo" 
   exit 1
fi

# Repair Nextcloud
echo "Running maintenance:repair..."
docker exec -u www-data nextcloud-app php occ maintenance:repair

# Scan all files
echo "Scanning all files..."
docker exec -u www-data nextcloud-app php occ files:scan --all

# Clear caches
echo "Clearing caches..."
docker exec -u www-data nextcloud-app php occ cache:clear

# Verify theme resources
echo "Verifying theme resources..."
docker exec -u www-data nextcloud-app php occ theming:config name "Nextcloud"
docker exec -u www-data nextcloud-app php occ theming:config url "https://nextcloud.com"
docker exec -u www-data nextcloud-app php occ theming:config color "#0082c9"

# Set up background jobs
echo "Setting up background jobs..."
docker exec -u www-data nextcloud-app php occ background:cron

echo "Repair completed successfully!"