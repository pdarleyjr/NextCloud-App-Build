#!/bin/bash

# Script to automatically merge Dependabot PRs
# This script uses the GitHub CLI (gh) to merge PRs in a specific order

echo "Merging Dependabot PRs..."

# Ensure GitHub CLI is installed
if ! command -v gh &> /dev/null; then
    echo "GitHub CLI (gh) is not installed. Please install it first."
    exit 1
fi

# Ensure user is authenticated with GitHub CLI
if ! gh auth status &> /dev/null; then
    echo "Please authenticate with GitHub CLI first using 'gh auth login'"
    exit 1
fi

# Define the order of PRs to merge
# This order is important to avoid compatibility issues
PR_NUMBERS=(
    1  # actions/upload-artifact from 3 to 4
    2  # phpstan/phpstan from ^1.10 to ^2.1
    3  # eslint-plugin-n from 16.6.2 to 17.16.2
    4  # style-loader from 3.3.4 to 4.0.0
    5  # babel-loader from 9.2.1 to 10.0.0
    6  # eslint-plugin-promise from 6.6.0 to 7.2.1
    7  # css-loader from 6.11.0 to 7.1.2
    8  # eslint from 8.57.1 to 9.22.0
    9  # webpack-cli from 5.1.4 to 6.0.1
)

# Repository information
REPO="pdarleyjr/NextCloud-App-Build"

# Merge each PR in order
for PR in "${PR_NUMBERS[@]}"; do
    echo "Merging PR #$PR..."
    
    # Check if PR exists and is open
    if ! gh pr view "$PR" --repo "$REPO" &> /dev/null; then
        echo "PR #$PR does not exist or you don't have access to it. Skipping."
        continue
    fi
    
    # Check if PR is open
    PR_STATE=$(gh pr view "$PR" --repo "$REPO" --json state --jq .state)
    if [ "$PR_STATE" != "OPEN" ]; then
        echo "PR #$PR is not open (state: $PR_STATE). Skipping."
        continue
    fi
    
    # Merge the PR
    if gh pr merge "$PR" --repo "$REPO" --squash --delete-branch; then
        echo "Successfully merged PR #$PR"
    else
        echo "Failed to merge PR #$PR. Please check for conflicts or other issues."
        exit 1
    fi
    
    # Wait a bit to allow GitHub to process the merge
    echo "Waiting for GitHub to process the merge..."
    sleep 5
done

echo "All PRs have been processed."