#!/bin/bash

echo "Running CSS validation for Nextcloud Appointments app..."

# Navigate to the project root
cd "$(dirname "$0")/.."

# Check if stylelint is installed
if ! command -v stylelint &> /dev/null; then
    echo "stylelint not found. Installing..."
    npm install -g stylelint stylelint-config-recommended-scss stylelint-scss
fi

# Run stylelint on SCSS files
echo "Validating SCSS files..."
npx stylelint "custom_apps/appointments/scss/**/*.scss" --config custom_apps/appointments/.stylelintrc.json

# Check exit code
if [ $? -eq 0 ]; then
    echo "✅ CSS validation passed successfully!"
else
    echo "❌ CSS validation failed. Please fix the issues above."
    exit 1
fi

# Offer to fix issues automatically
read -p "Would you like to automatically fix CSS issues? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Fixing CSS issues..."
    npx stylelint "custom_apps/appointments/scss/**/*.scss" --config custom_apps/appointments/.stylelintrc.json --fix
    echo "✅ CSS issues fixed. Please review the changes."
fi

echo "Done!"