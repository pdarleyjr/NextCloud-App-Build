#!/bin/bash

# Verify SCSS files for deprecation warnings and migration to the module system
# This script checks for common deprecated patterns and helps migrate to the new Sass module system

echo "Checking SCSS files for deprecated syntax..."

# Find all SCSS files
SCSS_FILES=$(find ./scss -name "*.scss")

# Initialize counters
DEPRECATED_IMPORT_COUNT=0
DEPRECATED_COLOR_FUNCTION_COUNT=0

# Check each file
for file in $SCSS_FILES; do
  echo "Analyzing $file..."
  
  # Check for @import (deprecated)
  IMPORT_COUNT=$(grep -c "@import" "$file" || true)
  if [ "$IMPORT_COUNT" -gt 0 ]; then
    DEPRECATED_IMPORT_COUNT=$((DEPRECATED_IMPORT_COUNT + IMPORT_COUNT))
    echo "  ⚠️ Found $IMPORT_COUNT @import statements (deprecated, use @use instead)"
    grep -n "@import" "$file"
  fi
  
  # Check for deprecated color functions
  COLOR_FUNCTION_COUNT=$(grep -c -E "darken\(|lighten\(|adjust-hue\(|saturate\(|desaturate\(" "$file" || true)
  if [ "$COLOR_FUNCTION_COUNT" -gt 0 ]; then
    DEPRECATED_COLOR_FUNCTION_COUNT=$((DEPRECATED_COLOR_FUNCTION_COUNT + COLOR_FUNCTION_COUNT))
    echo "  ⚠️ Found $COLOR_FUNCTION_COUNT deprecated color functions"
    grep -n -E "darken\(|lighten\(|adjust-hue\(|saturate\(|desaturate\(" "$file"
    echo "  💡 Replace with color.adjust() or color.scale() from the sass:color module"
  fi
  
  echo ""
done

echo "Summary:"
echo "Total SCSS files checked: $(echo "$SCSS_FILES" | wc -l)"
echo "Found $DEPRECATED_IMPORT_COUNT deprecated @import statements"
echo "Found $DEPRECATED_COLOR_FUNCTION_COUNT deprecated color functions"

if [ "$DEPRECATED_IMPORT_COUNT" -gt 0 ] || [ "$DEPRECATED_COLOR_FUNCTION_COUNT" -gt 0 ]; then
  echo -e "\nRecommendations:"
  echo "1. Replace @import with @use and namespace imports:"
  echo "   OLD: @import 'variables';"
  echo "   NEW: @use 'variables' as vars;"
  echo ""
  echo "2. Replace deprecated color functions with modern alternatives:"
  echo "   OLD: darken(\$color-primary, 7%);"
  echo "   NEW: @use 'sass:color';"
  echo "        color.adjust(\$color-primary, \$lightness: -7%);"
  echo ""
  echo "3. Run sass-migrator to help with the migration:"
  echo "   npx sass-migrator module --migrate-deps scss/**/*.scss"
else
  echo "No deprecated Sass syntax found! Your SCSS files are using modern syntax."
fi