# Contributing to Flux AI Media Alt Creator

Thank you for your interest in contributing to Flux AI Media Alt Creator! We welcome contributions from the community and are grateful for your help in making this plugin better.

## Getting Started

Before you begin, please familiarize yourself with WordPress plugin development standards and this plugin's architecture:

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [WordPress Plugin Development Handbook](https://developer.wordpress.org/plugins/)
- [GitHub Repository](https://github.com/stratease/flux-ai-media-alt-creator)

## Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/stratease/flux-ai-media-alt-creator.git
   cd flux-ai-media-alt-creator
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Run code quality checks:**
   ```bash
   composer run phpcs        # Check coding standards
   composer run phpstan      # Static analysis
   composer run test         # Run tests
   composer run quality      # Run all quality checks
   ```

## Pull Request Process

We follow the standard WordPress community pull request workflow. Before submitting a pull request, please ensure:

### 1. Code Quality

- **WordPress Coding Standards**: All code must follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- **PHP Version**: This plugin requires PHP 8.0 or higher
- **WordPress Version**: Requires WordPress 5.8 or higher
- **Code must pass all quality checks:**
  ```bash
  composer run quality
  ```

### 2. Testing

- Add or update tests for new functionality
- Ensure all existing tests pass
- Test your changes in a WordPress environment

### 3. Documentation

- Update code documentation (PHPDoc comments)
- Update this CONTRIBUTING.md if contributing guidelines change
- Update `readme.txt` if user-facing features change

### 4. Commit Messages

Follow [WordPress commit message guidelines](https://make.wordpress.org/core/handbook/best-practices/commit-messages/):

- Use imperative mood ("Add feature" not "Added feature" or "Adds feature")
- Keep the first line under 72 characters
- Reference issues/PRs when applicable
- Include a brief explanation if the commit is complex

Example:
```
Add bulk processing support for media library scan

Implements background job processing using Action Scheduler
to allow processing large media libraries without blocking
the admin interface.

Fixes #123
```

### 5. Pull Request Requirements

When submitting a pull request:

1. **Clear Description**: Explain what the PR does and why
2. **Reference Issues**: Link to any related issues
3. **Testing**: Describe how you tested your changes
4. **Breaking Changes**: Note any breaking changes if applicable
5. **Screenshots**: Include screenshots for UI changes

**PR Title Format**: `[Type] Brief description`

Types: `Feature`, `Bugfix`, `Enhancement`, `Documentation`, `Refactor`

Example: `[Bugfix] Fix PHP_VERSION escaping in version notice`

## Code Standards

### PHP Coding Standards

This plugin follows [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- Use tabs for indentation (not spaces)
- Use single quotes for strings when possible
- Use short arrays (`[]`) not long arrays (`array()`)
- Follow WordPress naming conventions
- All code must pass PHPCS checks:
  ```bash
  composer run phpcs
  ```

### Prefix Strategy

This plugin uses a consistent prefix strategy to ensure uniqueness:

- **Functions**: `flux_ai_media_alt_creator_*` (full prefix matching plugin slug)
- **Constants**: `FLUX_AI_MEDIA_ALT_CREATOR_*` (full prefix matching plugin slug)
- **Action Hooks/Filter Hooks**: `flux_ai_alt_creator/*` (shortened prefix for readability while maintaining uniqueness)
- **Options/Meta Keys**: `flux_ai_alt_creator*` (shortened prefix for readability while maintaining uniqueness)
- **Classes**: Properly namespaced under `FluxAIMediaAltCreator\` namespace

**Important**: All prefixes must be unique and specific to this plugin to avoid conflicts with other plugins.

### Security Best Practices

- Always escape output using appropriate functions (`esc_html()`, `esc_attr()`, `esc_url()`, etc.)
- Always sanitize input using appropriate functions (`sanitize_text_field()`, `sanitize_email()`, etc.)
- Use nonces for forms and AJAX requests
- Verify user capabilities before performing actions
- Never trust user input

### JavaScript/CSS Standards

- Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- Use WordPress's built-in JavaScript APIs when possible
- Enqueue scripts/styles properly using `wp_enqueue_script()` and `wp_enqueue_style()`
- Never output `<script>` or `<style>` tags directly in PHP

## Architecture Overview

### Directory Structure

```
flux-ai-media-alt-creator/
├── app/
│   ├── Http/
│   │   └── Controllers/     # REST API controllers
│   ├── Providers/           # Service providers
│   └── Services/            # Business logic services
├── assets/
│   └── js/
│       └── src/             # React application source
├── config/                  # Configuration files
├── vendor/                  # Composer dependencies
└── vendor-prefixed/         # Strauss-prefixed dependencies
```

### Service Architecture

- **Services**: Business logic, singleton pattern with `get_instance()`
- **Controllers**: Handle REST API requests, extend `BaseController`
- **Providers**: Register hooks and initialize services
- **Plugin.php**: Main plugin class, orchestrates initialization

### Key Concepts

- **Action Scheduler**: Used for background job processing
- **Strauss**: Namespace prefixing for common library dependencies
- **React**: Frontend admin interface (build process via Webpack)
- **WordPress REST API**: Backend API for React frontend

## Development Workflow

1. **Create a branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes:**
   - Write clean, documented code
   - Follow the code standards above
   - Add tests if applicable

3. **Test your changes:**
   ```bash
   composer run quality
   ```

4. **Commit your changes:**
   ```bash
   git commit -m "Your commit message"
   ```

5. **Push to your fork:**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request** on GitHub with a clear description

## Reporting Issues

Before reporting an issue:

1. Check existing issues to see if it's already reported
2. Ensure you're using the latest version
3. Provide as much detail as possible:
   - WordPress version
   - PHP version
   - Plugin version
   - Steps to reproduce
   - Expected vs actual behavior
   - Error messages (if any)

## Questions?

If you have questions about contributing, please:

- Open an issue on GitHub
- Check existing documentation
- Review the codebase for examples

## License

By contributing to Flux AI Media Alt Creator, you agree that your contributions will be licensed under the GPL-2.0-or-later license, same as the plugin itself.

---

Thank you for contributing to Flux AI Media Alt Creator! 🙏

