#!/bin/bash
echo "Setting up Nextcloud development environment..."

# Install dependencies
sudo apt update && sudo apt install -y php php-cli php-mbstring php-xml php-zip unzip curl jq mariadb-client

# Set up Docker Compose
docker-compose up -d

echo "Nextcloud is now running!"