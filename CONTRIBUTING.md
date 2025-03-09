# Contributing to Nextcloud App Build

Thank you for your interest in contributing to this project! Here are some guidelines to help you get started.

## Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## How Can I Contribute?

### Reporting Bugs

- Check if the bug has already been reported in the [Issues](https://github.com/pdarleyjr/NextCloud-App-Build/issues)
- If not, create a new issue with a clear title and description
- Include steps to reproduce, expected behavior, and actual behavior
- Add screenshots if applicable
- Specify your environment (OS, browser, Nextcloud version, etc.)

### Suggesting Features

- Check if the feature has already been suggested in the [Issues](https://github.com/pdarleyjr/NextCloud-App-Build/issues)
- If not, create a new issue with a clear title and description
- Explain why this feature would be useful to most users
- Suggest an implementation if possible

### Pull Requests

1. Fork the repository
2. Create a new branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Make your changes
4. Run tests to ensure your changes don't break existing functionality
5. Commit your changes with a clear commit message:
   ```bash
   git commit -m "Add: brief description of your changes"
   ```
6. Push to your branch:
   ```bash
   git push origin feature/your-feature-name
   ```
7. Create a pull request to the `main` branch

## Development Workflow

### Setting Up Development Environment

1. Clone the repository
2. Install dependencies
3. Start the Docker environment
4. Make your changes
5. Test your changes

### Coding Standards

- Follow the [Nextcloud Coding Style](https://docs.nextcloud.com/server/latest/developer_manual/getting_started/codingguidelines.html)
- Write clear, commented, and testable code
- Keep functions small and focused
- Use meaningful variable and function names

### Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

- `feat:` for new features
- `fix:` for bug fixes
- `docs:` for documentation changes
- `style:` for formatting changes
- `refactor:` for code refactoring
- `test:` for adding or modifying tests
- `chore:` for maintenance tasks

### Testing

- Write tests for new features
- Ensure all tests pass before submitting a pull request
- Test your changes in different environments if possible

## License

By contributing to this project, you agree that your contributions will be licensed under the project's [AGPL-3.0 License](LICENSE).