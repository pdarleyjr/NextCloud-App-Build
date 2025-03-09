# Nextcloud App Build

A modern, containerized development environment for Nextcloud app development with GitHub Codespaces integration.

## Overview

This repository provides a standardized development environment for Nextcloud app development, allowing you to:

- Develop Nextcloud apps using Docker containers
- Seamlessly work from anywhere using GitHub Codespaces
- Maintain consistent development environments across team members
- Automate testing and deployment workflows

## Features

- **Docker-based Development**: Preconfigured Docker environment for Nextcloud app development
- **GitHub Codespaces Integration**: Work from any device with an internet connection
- **Development Tools**: Includes debugging, linting, and testing tools
- **Documentation**: Comprehensive guides for setup and development

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Git
- (Optional) GitHub account for Codespaces

### Local Development Setup

1. Clone this repository:
   ```bash
   git clone https://github.com/pdarleyjr/NextCloud-App-Build.git
   cd NextCloud-App-Build
   ```

2. Start the Docker environment:
   ```bash
   docker-compose up -d
   ```

3. Access Nextcloud at `http://localhost:8080`

### Using GitHub Codespaces

1. Navigate to the repository on GitHub
2. Click "Code" > "Open with Codespaces"
3. Create a new codespace or select an existing one
4. The development environment will be automatically set up

## Project Structure

```
NextCloud-App-Build/
├── .github/                  # GitHub workflows and templates
├── docker/                   # Docker configuration files
├── docs/                     # Documentation
├── src/                      # Source code for your Nextcloud app
│   ├── appinfo/              # App metadata
│   ├── lib/                  # PHP classes
│   ├── templates/            # Templates
│   └── js/                   # JavaScript files
├── tests/                    # Test files
├── .gitignore                # Git ignore file
├── docker-compose.yml        # Docker Compose configuration
├── LICENSE                   # License file
└── README.md                 # This file
```

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the AGPL-3.0 License - see the [LICENSE](LICENSE) file for details.