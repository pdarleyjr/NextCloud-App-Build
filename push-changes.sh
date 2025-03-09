#!/bin/bash

# Script to commit and push changes to GitHub

echo "Committing and pushing changes to GitHub..."

# Configure Git
git config --global user.name "pdarleyjr"
git config --global user.email "pdarleyjr@gmail.com"

# Add all changes
git add .

# Commit changes
git commit -m "Integrated therapist scheduling and payments app"

# Push to GitHub
git push origin main

echo "Changes pushed successfully!"