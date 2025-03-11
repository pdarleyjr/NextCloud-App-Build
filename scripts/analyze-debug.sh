#!/bin/bash
set -euo pipefail

echo "=== Starting Repository Analysis and Debug ==="

# Print git status info if available
if command -v git >/dev/null 2>&1; then
    echo "--- Git Status ---"
    git status
    echo ""
fi

# Run eslint if installed
if command -v eslint >/dev/null 2>&1; then
    echo "--- Running ESLint ---"
    eslint .
    echo ""
else
    echo "ESLint not detected, skipping linting."
    echo ""
fi

# If package.json exists, assume NodeJS project and run lint and tests
if [ -f "package.json" ]; then
    # Check for lint script in package.json
    if npm run | grep -q "lint"; then
        echo "--- Running npm lint ---"
        npm run lint || echo "Linting failed"
        echo ""
    fi

    # Run tests if available
    if npm run | grep -q "test"; then
        echo "--- Running npm test ---"
        npm test || echo "Tests failed"
        echo ""
    fi
fi

echo "=== Analysis and Debug Completed ==="
