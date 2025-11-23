# Contributing to Word for Laravel

Thank you for your interest in this project! This guide explains how to set up your environment, propose changes, and submit high‑quality pull requests.

## Prerequisites

- PHP >= 8.1
- Composer
- Typical Laravel extensions/requirements

## Project setup

1. Fork the repository and clone your fork.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Make sure the test suite runs locally:
   ```bash
   composer test
   ```

## Recommended workflow

- Create a feature branch from `main`:
  ```bash
  git checkout -b feat/my-feature
  ```
- Commit small, atomic changes with clear messages (Conventional Commits recommended: `feat:`, `fix:`, `docs:`, `test:`, `refactor:`…).
- Update documentation (README, examples) when relevant.
- Open a Pull Request that clearly describes the context, motivation, and solution.

## Coding standards

- Follow PSR-12.
- Follow common Laravel conventions (naming, directory structure, facades vs. dependency injection, etc.).
- Keep code simple, tested, and documented when appropriate.

## Quality and static analysis

- PHPStan is configured in this project. Before opening a PR, run:
  ```bash
  vendor/bin/phpstan analyse
  ```
- Fix reported issues. If a rule is too strict for a specific case, justify it in the PR description.

## Tests

- Run the test suite:
  ```bash
  composer test
  ```
- For coverage:
  ```bash
  composer test-coverage
  ```
- Add tests for any new feature or bug fix. PRs without tests may be declined.

## Commit and PR style

- Use descriptive and concise commit messages.
- In your PR description, please include:
  - The problem (or feature) and context
  - The proposed solution
  - Any potential breaking changes (BC breaks)
  - How to test the change

## Security

- Do not disclose vulnerabilities publicly. Email: inanakomlan@gmail.com

## License

By contributing, you agree that your contributions are licensed under the MIT License, as stated in `LICENSE.md`.
