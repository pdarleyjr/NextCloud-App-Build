FROM mcr.microsoft.com/vscode/devcontainers/base:ubuntu

# Install additional dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set up environment to improve build performance
ENV NODE_OPTIONS=--max-old-space-size=4096