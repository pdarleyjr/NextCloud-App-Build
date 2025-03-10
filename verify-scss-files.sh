#!/bin/bash

# Script to verify and rebuild SCSS/CSS files
# This script checks for the existence of SCSS/CSS files and rebuilds them if necessary

echo "Verifying SCSS/CSS files..."

# Check if the appointments app CSS directory exists
if [ ! -d "custom_apps/appointments/css" ]; then
  echo "Creating CSS directory for appointments app..."
  mkdir -p custom_apps/appointments/css
fi

# Check if the main CSS files exist
if [ ! -f "custom_apps/appointments/css/style.css" ] || [ ! -f "custom_apps/appointments/css/form.css" ] || [ ! -f "custom_apps/appointments/css/hide-app.css" ]; then
  echo "Some CSS files are missing. Rebuilding..."
  
  # Navigate to the appointments app directory
  cd custom_apps/appointments
  
  # Check if npm is installed
  if command -v npm &> /dev/null; then
    echo "Running npm build to generate CSS files..."
    npm run build
  else
    echo "npm not found. Please install npm and run 'npm run build' in the custom_apps/appointments directory."
  fi
  
  cd ../..
else
  echo "All CSS files exist."
fi

# Verify if the CSS files were created successfully
if [ -f "custom_apps/appointments/css/style.css" ] && [ -f "custom_apps/appointments/css/form.css" ] && [ -f "custom_apps/appointments/css/hide-app.css" ]; then
  echo "✅ CSS files verified successfully."
else
  echo "❌ Some CSS files are still missing. Please check the build process."
fi

# Check if Nextcloud is running in Docker
if command -v docker &> /dev/null && docker ps | grep -q "nextcloud"; then
  echo "Repairing and updating Nextcloud theme..."
  
  # Run Nextcloud maintenance commands with the correct user ID (33)
  docker exec -u 33 -it nextcloud-app php occ maintenance:repair
  docker exec -u 33 -it nextcloud-app php occ maintenance:theme:update
  
  echo "✅ Nextcloud theme updated."
else
  echo "Nextcloud Docker container not found or not running."
fi

echo "SCSS/CSS verification complete."