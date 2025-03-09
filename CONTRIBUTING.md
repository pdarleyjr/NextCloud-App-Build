# Contributing to NextCloud App Build

Thank you for considering contributing to this NextCloud app! This document outlines the process for contributing to the project.

## Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## How Can I Contribute?

### Reporting Bugs

This section guides you through submitting a bug report. Following these guidelines helps maintainers understand your report, reproduce the issue, and find related reports.

Before creating bug reports, please check the issue tracker as you might find that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* **Use a clear and descriptive title** for the issue to identify the problem.
* **Describe the exact steps which reproduce the problem** in as many details as possible.
* **Provide specific examples to demonstrate the steps**. Include links to files or GitHub projects, or copy/pasteable snippets, which you use in those examples.
* **Describe the behavior you observed after following the steps** and point out what exactly is the problem with that behavior.
* **Explain which behavior you expected to see instead and why.**
* **Include screenshots and animated GIFs** which show you following the described steps and clearly demonstrate the problem.
* **If the problem wasn't triggered by a specific action**, describe what you were doing before the problem happened.

### Suggesting Enhancements

This section guides you through submitting an enhancement suggestion, including completely new features and minor improvements to existing functionality.

* **Use a clear and descriptive title** for the issue to identify the suggestion.
* **Provide a step-by-step description of the suggested enhancement** in as many details as possible.
* **Provide specific examples to demonstrate the steps**. Include copy/pasteable snippets which you use in those examples.
* **Describe the current behavior** and **explain which behavior you expected to see instead** and why.
* **Include screenshots and animated GIFs** which help you demonstrate the steps or point out the part of the app which the suggestion is related to.
* **Explain why this enhancement would be useful** to most users.

### Pull Requests

* Fill in the required template
* Do not include issue numbers in the PR title
* Include screenshots and animated GIFs in your pull request whenever possible
* Follow the [PHP](#php-styleguide) and [JavaScript](#javascript-styleguide) styleguides
* Document new code based on the [Documentation Styleguide](#documentation-styleguide)
* End all files with a newline

## Styleguides

### Git Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally after the first line
* Consider starting the commit message with an applicable emoji:
    * 🎨 `:art:` when improving the format/structure of the code
    * 🐎 `:racehorse:` when improving performance
    * 🚱 `:non-potable_water:` when plugging memory leaks
    * 📝 `:memo:` when writing docs
    * 🐛 `:bug:` when fixing a bug
    * 🔥 `:fire:` when removing code or files
    * 💚 `:green_heart:` when fixing the CI build
    * ✅ `:white_check_mark:` when adding tests
    * 🔒 `:lock:` when dealing with security
    * ⬆️ `:arrow_up:` when upgrading dependencies
    * ⬇️ `:arrow_down:` when downgrading dependencies
    * 👕 `:shirt:` when removing linter warnings

### PHP Styleguide

* Follow the [PSR-12 coding standard](https://www.php-fig.org/psr/psr-12/)
* Use PHP 7.4 or higher features where appropriate
* Document all methods and classes with PHPDoc comments

### JavaScript Styleguide

* Use ES6 features where appropriate
* Use semicolons
* 2 spaces for indentation
* Prefer single quotes over double quotes
* 80 character line length
* Name variables and functions with camelCase

### Documentation Styleguide

* Use [Markdown](https://daringfireball.net/projects/markdown) for documentation
* Reference methods and classes in markdown with the custom `{@link}` syntax:
    * Reference classes with `{@link ClassName}`
    * Reference methods with `{@link ClassName::methodName}`

## Additional Notes

### Issue and Pull Request Labels

This section lists the labels we use to help us track and manage issues and pull requests.

* `bug` - Issues that are bugs
* `documentation` - Issues or PRs related to documentation
* `enhancement` - Issues that are feature requests or PRs that implement features
* `good first issue` - Good for newcomers
* `help wanted` - Extra attention is needed
* `invalid` - Issues that are invalid or non-reproducible
* `question` - Issues that are questions or need more information
* `wontfix` - Issues that will not be worked on