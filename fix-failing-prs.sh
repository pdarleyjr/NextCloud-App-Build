#!/bin/bash

# Script to create a fix for the failing dependabot PRs
# This script will create a new branch with all necessary fixes and create a PR

set -e

# Repository information
REPO="NextCloud-App-Build"
OWNER="pdarleyjr"
BRANCH_NAME="fix/dependency-compatibility-updates"
PR_TITLE="Fix workflow and dependency compatibility issues"

# GitHub token from environment or gh auth
TOKEN=${GITHUB_TOKEN:-$(gh auth token)}

if [ -z "$TOKEN" ]; then
    echo "Error: GitHub token not found. Set GITHUB_TOKEN environment variable or login with 'gh auth login'."
    exit 1
fi

echo "Creating fix branch and PR for dependency compatibility issues..."

# Create a new branch
git checkout -b $BRANCH_NAME

# Stage the modified files
git add webpack.config.js .eslintrc.js .github/workflows/ci.yml .github/workflows/lint-css.yml

# Commit the changes
git commit -m "Fix dependency compatibility issues

- Update css-loader configuration to work with v7.1.2
- Update ESLint configuration to work with eslint 9.22.0 and eslint-plugin-promise 7.2.1
- Update Node.js version in workflows to 20.x for compatibility with webpack-cli 6.0.1"

# Push the branch
git push -u origin $BRANCH_NAME

# Create a PR
PR_BODY="This PR fixes compatibility issues with the following dependency updates:

1. css-loader 7.1.2: Updated webpack config to set namedExport to false
2. eslint 9.22.0 & eslint-plugin-promise 7.2.1: Added proper configuration
3. webpack-cli 6.0.1: Updated Node.js version to 20.x and adjusted webpack exports

These changes should fix the failing workflows for PRs #6, #7, #8, and #9."

# Create PR using GitHub API
curl -s -X POST -H "Authorization: token $TOKEN" \
    -H "Accept: application/vnd.github.v3+json" \
    -d "{\"title\":\"$PR_TITLE\",\"body\":\"$PR_BODY\",\"head\":\"$BRANCH_NAME\",\"base\":\"main\"}" \
    "https://api.github.com/repos/$OWNER/$REPO/pulls"

echo "PR created successfully! Once this PR is merged, you can run ./auto-merge-dependabot-prs.sh to merge the fixed dependabot PRs."

# Create a helper script to close existing PRs that this PR supersedes
cat > close-superseded-prs.sh << 'EOF'
#!/bin/bash

# Script to close PRs that are superseded by the fix PR
# Usage: ./close-superseded-prs.sh [PR#]

# Repository information
REPO="NextCloud-App-Build"
OWNER="pdarleyjr"
FIX_PR=${1:-""}

if [ -z "$FIX_PR" ]; then
    echo "Error: Please provide the PR number of the fix PR as the first argument."
    exit 1
fi

# GitHub token from environment or gh auth
TOKEN=${GITHUB_TOKEN:-$(gh auth token)}

if [ -z "$TOKEN" ]; then
    echo "Error: GitHub token not found. Set GITHUB_TOKEN environment variable or login with 'gh auth login'."
    exit 1
fi

# List of PRs to close after the fix PR is merged
prs_to_close=(6 7 8 9)

for pr in "${prs_to_close[@]}"; do
    echo "Closing PR #$pr as it is superseded by PR #$FIX_PR..."
    
    # Add comment
    curl -s -X POST -H "Authorization: token $TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -d "{\"body\":\"This PR is superseded by #$FIX_PR which includes the necessary compatibility fixes for this dependency update.\"}" \
        "https://api.github.com/repos/$OWNER/$REPO/issues/$pr/comments"
    
    # Close the PR
    curl -s -X PATCH -H "Authorization: token $TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -d '{"state":"closed"}' \
        "https://api.github.com/repos/$OWNER/$REPO/pulls/$pr"
    
    echo "PR #$pr has been closed."
done

echo "All superseded PRs have been closed."
EOF

chmod +x close-superseded-prs.sh

echo "Created close-superseded-prs.sh script to close the PRs that this fix supersedes."
echo "Run it with: ./close-superseded-prs.sh [new-pr-number] after the fix PR is created."