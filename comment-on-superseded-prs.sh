#!/bin/bash
set -euo pipefail

# Ensure Bash version supports associative arrays (requires Bash 4+)
if ((BASH_VERSINFO[0] < 4)); then
    echo "Error: This script requires Bash version 4 or higher." >&2
    exit 1
fi

# Script to add comments to PRs that are superseded by the fix PR
# Usage: ./comment-on-superseded-prs.sh

# Repository information
REPO="NextCloud-App-Build"
OWNER="pdarleyjr"
FIX_PR="17"  # Our new PR that fixes the compatibility issues

# GitHub token from environment or gh auth
TOKEN=${GITHUB_TOKEN:-$(gh auth token)}

if [ -z "$TOKEN" ]; then
    echo "Error: GitHub token not found. Set GITHUB_TOKEN environment variable or login with 'gh auth login'."
    exit 1
fi

# List of PRs that are affected by our fix
declare -A pr_messages=(
    ["6"]="This PR (bump eslint-plugin-promise from 6.6.0 to 7.2.1) is addressed by #${FIX_PR} which includes the necessary ESLint configuration updates for compatibility."
    ["7"]="This PR (bump css-loader from 6.11.0 to 7.1.2) is addressed by #${FIX_PR} which includes the necessary webpack config updates to handle the namedExport changes."
    ["8"]="This PR (bump eslint from 8.57.1 to 9.22.0) is addressed by #${FIX_PR} which includes the necessary ESLint configuration updates for compatibility."
    ["9"]="This PR (bump webpack-cli from 5.1.4 to 6.0.1) is addressed by #${FIX_PR} which includes Node.js version updates and webpack config adjustments for compatibility."
)

for pr in "${!pr_messages[@]}"; do
    message="${pr_messages[$pr]}"
    echo "Adding comment to PR #$pr..."
    
    # Add comment explaining that this PR is addressed by our fix PR
    curl -s -X POST -H "Authorization: token $TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -d "{\"body\":\"$message\n\nOnce #${FIX_PR} is merged, this PR should be able to pass CI checks and can be merged as well.\"}" \
        "https://api.github.com/repos/$OWNER/$REPO/issues/$pr/comments"
    
    echo "Comment added to PR #$pr"
done

echo "All PR comments have been added."
echo "Next steps:"
echo "1. Merge PR #$FIX_PR to fix the compatibility issues"
echo "2. After merging, the dependabot PRs should pass CI and can be merged automatically"
echo "3. Alternatively, you can close the dependabot PRs if you prefer to combine all the updates in PR #$FIX_PR"