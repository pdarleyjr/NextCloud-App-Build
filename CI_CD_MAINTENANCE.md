# CI/CD Pipeline Maintenance Guide

This document provides information about the CI/CD pipeline in this repository and guidelines for maintaining it.

## Overview

The CI/CD pipeline in this repository consists of several GitHub Actions workflows:

1. **CI Workflow** (`.github/workflows/ci.yml`): Main CI pipeline that runs linting, testing, and building.
2. **CSS Linting Workflow** (`.github/workflows/lint-css.yml`): Specific workflow for linting CSS files.
3. **PR Automation Workflow** (`.github/workflows/pr-automation.yml`): Automates PR labeling and reviewer assignment.
4. **Codespace PR Review Workflow** (`.github/workflows/codespace-pr-review.yml`): Sets up GitHub Codespaces for PR reviews.
5. **Project Management Workflow** (`.github/workflows/project-management.yml`): Manages project boards and issue tracking.

## Recent Updates

The following updates were made to fix workflow and dependency issues:


1. Updated GitHub Actions to their latest versions:
   - `actions/checkout` from v3 to v4
   - `actions/setup-node` from v3 to v4
   - `actions/upload-artifact` from v3 to v4
   - `actions/add-to-project` from v0.5.0 to v0.6.0

2. Updated npm dependencies to their latest versions:
   - `babel-loader` from 9.x to 10.x
   - `css-loader` from 6.x to 7.x
   - `eslint` from 8.x to 9.x
   - `eslint-plugin-n` from 16.x to 17.x
   - `eslint-plugin-promise` from 6.x to 7.x
   - `style-loader` from 3.x to 4.x
   - `webpack-cli` from 5.x to 6.x

3. Updated PHP dependencies:
   - `phpstan/phpstan` from 1.x to 2.x

4. Updated configuration files to be compatible with the new versions:
   - Updated webpack configuration for new loader versions
   - Updated ESLint configuration for ESLint 9
   - Updated PHPStan analysis level

5. Fixed issues with GitHub Actions workflows:
   - Removed invalid parameters from `actions/upload-artifact@v4` configuration
   - Created script to automatically merge Dependabot PRs in the correct order

## Maintenance Guidelines

### Updating Dependencies

1. **Dependabot PRs**: This repository uses Dependabot to automatically create PRs for dependency updates. When merging these PRs, consider the following:
   - Major version updates may require configuration changes
   - Test the changes locally before merging
   - Consider the order of merging to avoid compatibility issues

2. **GitHub Actions**: When updating GitHub Actions, ensure that:
   - All workflows use consistent versions of the same actions
   - Breaking changes in actions are addressed across all workflows
   - Required inputs and outputs are updated according to the action's documentation

3. **npm Packages**: When updating npm packages, ensure that:
   - Webpack configuration is updated if loader or plugin APIs change
   - ESLint configuration is updated if ESLint or its plugins change
   - Test the build process locally before committing

4. **PHP Dependencies**: When updating PHP dependencies, ensure that:
   - Composer scripts are updated if necessary
   - PHPStan configuration is updated if PHPStan changes

### Troubleshooting CI/CD Issues

If you encounter issues with the CI/CD pipeline:

1. **Check Workflow Logs**: Examine the GitHub Actions workflow logs to identify the specific step that's failing.

2. **Verify Action Versions**: Ensure that all actions are using compatible versions.

3. **Test Locally**: Try to reproduce the issue locally to debug more effectively.

4. **Check Dependencies**: Verify that all dependencies are compatible with each other.

5. **Review Configuration**: Ensure that configuration files are correctly set up for the current versions of dependencies.

### Adding New Workflows

When adding new workflows:

1. Use the latest versions of GitHub Actions
2. Maintain consistency with existing workflows
3. Document the purpose and behavior of the workflow
4. Test the workflow thoroughly before merging

## Useful Commands

- **Merge Dependabot PRs**: Use the `merge-dependabot-prs.sh` script to automatically merge Dependabot PRs in the correct order.
- **Auto-Merge Dependabot PRs**: Use the `auto-merge-dependabot-prs.sh` script to automatically merge Dependabot PRs in the correct order.
- **Run Tests Locally**: Use `npm test` to run JavaScript tests and `composer test` to run PHP tests.
- **Lint Code**: Use `npm run lint` for JavaScript linting and `composer lint` for PHP linting.
- **Build Project**: Use `npm run build` to build the project.

## Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)
- [Webpack Documentation](https://webpack.js.org/)
- [ESLint Documentation](https://eslint.org/)
- [PHPStan Documentation](https://phpstan.org/)