#!/bin/bash

# Script to automatically merge dependabot PRs that pass CI checks
# This script can be run in a GitHub Action workflow

# Required environment variables:
# - GITHUB_TOKEN: GitHub access token with repo scope

set -e

if [ -z "$GITHUB_TOKEN" ]; then
    echo "Error: GITHUB_TOKEN environment variable is required"
    exit 1
fi

# Get repository info from environment or parameters
GITHUB_REPOSITORY=${GITHUB_REPOSITORY:-${1:-""}}
if [ -z "$GITHUB_REPOSITORY" ]; then
    echo "Error: GitHub repository not specified"
    exit 1
fi

OWNER=$(echo "$GITHUB_REPOSITORY" | cut -d '/' -f 1)
REPO=$(echo "$GITHUB_REPOSITORY" | cut -d '/' -f 2)

echo "Checking for auto-mergeable PRs in $OWNER/$REPO..."

# Fetch dependabot PRs that are open
PULL_REQUESTS=$(curl -s -H "Authorization: token $GITHUB_TOKEN" \
    "https://api.github.com/repos/$OWNER/$REPO/pulls?state=open" | \
    jq '.[] | select(.user.login | contains("dependabot")) | {number: .number, title: .title, head: .head.ref, node_id: .node_id}')

if [ -z "$PULL_REQUESTS" ]; then
    echo "No open dependabot PRs found."
    exit 0
fi

echo "$PULL_REQUESTS" | jq -r '"Found PR #\(.number): \(.title)"'

# For each PR, check if it passes all required checks
echo "$PULL_REQUESTS" | jq -c '.' | while read -r PR; do
    PR_NUMBER=$(echo "$PR" | jq -r '.number')
    PR_TITLE=$(echo "$PR" | jq -r '.title')
    PR_NODE_ID=$(echo "$PR" | jq -r '.node_id')
    
    echo "Checking status of PR #$PR_NUMBER: $PR_TITLE"
    
    # Get PR details including mergeable_state
    PR_DETAILS=$(curl -s -H "Authorization: token $GITHUB_TOKEN" \
        "https://api.github.com/repos/$OWNER/$REPO/pulls/$PR_NUMBER")
    
    MERGEABLE=$(echo "$PR_DETAILS" | jq -r '.mergeable')
    MERGEABLE_STATE=$(echo "$PR_DETAILS" | jq -r '.mergeable_state')
    
    echo "  Mergeable: $MERGEABLE, State: $MERGEABLE_STATE"
    
    # Only proceed if PR is mergeable and passed all checks
    if [ "$MERGEABLE" != "true" ] || [ "$MERGEABLE_STATE" != "clean" ]; then
        echo "  PR #$PR_NUMBER cannot be auto-merged at this time."
        continue
    fi
    
    # Check if it's a patch or minor version bump (can be configured based on needs)
    if echo "$PR_TITLE" | grep -q -E "bump .* from .* to .*"; then
        # For dependency update PRs, we might want to auto-approve
        echo "  Approving PR #$PR_NUMBER"
        
        # Approve the PR using GraphQL API
        curl -s -X POST -H "Authorization: token $GITHUB_TOKEN" \
            -H "Content-Type: application/json" \
            -d "{\"query\":\"mutation { addPullRequestReview(input: {pullRequestId: \\\"$PR_NODE_ID\\\", event: APPROVE}) { clientMutationId } }\"}" \
            "https://api.github.com/graphql"
        
        echo "  Merging PR #$PR_NUMBER with squash merge"
        
        # Merge the PR
        MERGE_RESPONSE=$(curl -s -X PUT -H "Authorization: token $GITHUB_TOKEN" \
            -H "Accept: application/vnd.github.v3+json" \
            -d '{"merge_method":"squash"}' \
            "https://api.github.com/repos/$OWNER/$REPO/pulls/$PR_NUMBER/merge")
        
        if echo "$MERGE_RESPONSE" | jq -e '.merged == true' > /dev/null; then
            echo "  Successfully merged PR #$PR_NUMBER"
        else
            ERROR_MSG=$(echo "$MERGE_RESPONSE" | jq -r '.message')
            echo "  Failed to merge PR #$PR_NUMBER: $ERROR_MSG"
        fi
    else
        echo "  PR #$PR_NUMBER is not a standard dependency update PR, skipping auto-merge"
    fi
done

echo "Automatic PR processing completed"