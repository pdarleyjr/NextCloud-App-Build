#!/bin/bash

# Script to manually merge dependabot PRs
# Usage: ./merge-dependabot-prs.sh [repository] [owner]

# Default values
REPO=${1:-"NextCloud-App-Build"}
OWNER=${2:-"pdarleyjr"}
TOKEN=${GITHUB_TOKEN:-$(gh auth token)}

if [ -z "$TOKEN" ]; then
    echo "Error: GitHub token not found. Set GITHUB_TOKEN environment variable or login with 'gh auth login'."
    exit 1
fi

echo "Fetching open PRs from dependabot for $OWNER/$REPO..."

# Get all open PRs by dependabot
PULL_REQUESTS=$(curl -s -H "Authorization: token $TOKEN" \
    "https://api.github.com/repos/$OWNER/$REPO/pulls?state=open" | \
    jq '.[] | select(.user.login | contains("dependabot")) | {number: .number, title: .title}')

if [ -z "$PULL_REQUESTS" ]; then
    echo "No open dependabot PRs found."
    exit 0
fi

echo "Found the following dependabot PRs:"
echo "$PULL_REQUESTS" | jq -r '"#\(.number): \(.title)"'

echo ""
echo "Do you want to merge all these PRs? (y/n)"
read CONFIRM

if [ "$CONFIRM" != "y" ]; then
    echo "Operation cancelled."
    exit 0
fi

echo "$PULL_REQUESTS" | jq -r '.number' | while read PR_NUMBER; do
    echo "Merging PR #$PR_NUMBER..."
    
    # Check if PR can be merged
    MERGEABLE=$(curl -s -H "Authorization: token $TOKEN" \
        "https://api.github.com/repos/$OWNER/$REPO/pulls/$PR_NUMBER" | \
        jq -r '.mergeable')
    
    if [ "$MERGEABLE" != "true" ]; then
        echo "PR #$PR_NUMBER cannot be automatically merged. Skipping."
        continue
    fi
    
    # Merge the PR
    RESPONSE=$(curl -s -X PUT -H "Authorization: token $TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -d '{"merge_method":"squash"}' \
        "https://api.github.com/repos/$OWNER/$REPO/pulls/$PR_NUMBER/merge")
    
    if echo "$RESPONSE" | jq -e '.merged == true' > /dev/null; then
        echo "Successfully merged PR #$PR_NUMBER"
    else
        ERROR_MSG=$(echo "$RESPONSE" | jq -r '.message')
        echo "Failed to merge PR #$PR_NUMBER: $ERROR_MSG"
    fi
done

echo "Operation completed."