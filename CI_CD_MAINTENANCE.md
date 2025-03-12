# CI/CD Maintenance Guide

This document provides guidance on maintaining the CI/CD pipeline for the NextCloud-App-Build repository.

## GitHub Actions Workflows

The repository uses several GitHub Actions workflows:

1. **CI (Continuous Integration)** - Runs linting, testing, and building processes
2. **Lint CSS** - Specifically checks SCSS/CSS files for style issues
3. **Project Management** - Automates project board management
4. **PR Automation** - Handles pull request automation

## Node.js Version

The repository requires Node.js version 18.19.0 or higher due to dependencies like webpack-cli 6.0+ which mandates this version.

## Dependencies Management

### Dependabot

Dependabot is configured to automatically create pull requests for:

- GitHub Actions (weekly)
- npm packages (weekly)
- Composer packages (weekly)

The configuration file is located at `.github/dependabot.yml`.

### Managing Dependabot PRs

Two scripts are provided to manage Dependabot pull requests:

1. `merge-dependabot-prs.sh` - For manually merging Dependabot PRs
2. `auto-merge-dependabot-prs.sh` - For automatically merging Dependabot PRs in CI

#### Usage:

To manually merge Dependabot PRs:

```bash
./merge-dependabot-prs.sh [repository] [owner]
```

Default values are used if not provided:
- Repository: NextCloud-App-Build
- Owner: pdarleyjr

## Common Issues and Solutions

### CSS Loader Issues

When updating css-loader from 6.x to 7.x:
- The `modules.namedExport` default changed to `true`
- You may need to update import statements accordingly or set it explicitly to `false`

### ESLint Plugin Issues

When updating eslint-plugin-promise:
- Review the plugin's documentation for new rules and options
- Update the ESLint configuration as needed

### Webpack CLI Issues

When updating webpack-cli from 5.x to 6.x:
- It requires Node.js 18.12.0 or higher
- Some commands may be deprecated or have changed syntax

## Security Scanning

The CI pipeline includes OWASP Dependency-Check for security scanning.

## Best Practices

1. Always test dependency updates in a separate branch before merging
2. Use dependabot groups to update related packages together (configured in dependabot.yml)
3. Keep Node.js and PHP versions updated regularly
4. Regularly review and update GitHub Actions versions