#!/bin/bash

# Script to keep GitHub Codespaces session alive
# This script sends a periodic request to GitHub API to prevent session timeout
# Default timeout for GitHub Codespaces is 3,600 seconds (1 hour)

echo "Starting GitHub Codespaces session keepalive script..."
echo "This script will ping GitHub API every 5 minutes to keep your session active."
echo "Press Ctrl+C to stop."

while true; do
  echo "Pinging GitHub API to keep session alive at $(date)"
  curl -s "https://api.github.com" > /dev/null
  sleep 300  # Sleep for 5 minutes (300 seconds)
done