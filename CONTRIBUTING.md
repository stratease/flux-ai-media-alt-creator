# Contributing to Flux AI Alt Text & Accessibility Audit

Thank you for your interest in contributing to Flux AI Alt Text & Accessibility Audit! We welcome contributions from the community and are grateful for your help in making this plugin better.

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

3. **Build the frontend:**
   ```bash
   npm run build          # Production build
   npm run dev            # Development build with watch mode
   npm run start          # Dev server on port 3002 with HMR
   ```

4. **Run code quality checks:**
   ```bash
   composer run phpcs        # Check coding standards
   composer run phpstan      # Static analysis
   composer run test         # Run tests
   composer run quality      # Run all quality checks (phpcs + phpstan + tests)
   ```

5. **Run E2E tests (Playwright):**
   ```bash
   npx playwright test
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
- Run E2E regression tests when modifying admin UI

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
├── flux-ai-media-alt-creator.php  # Main plugin bootstrap (constants, hooks, activation/deactivation)
├── index.php                      # Security file
├── composer.json                  # PHP dependencies & Strauss config
├── package.json                   # Node dependencies & build scripts
├── webpack.config.js              # Webpack build configuration
├── readme.txt                     # WordPress.org plugin readme
├── CONTRIBUTING.md                # This file
│
├── app/                           # PHP application code (PSR-4: FluxAIMediaAltCreator\App\)
│   ├── Plugin.php                 # Main plugin class — initializes all services and providers
│   ├── Http/
│   │   └── Controllers/
│   │       ├── BaseController.php       # Abstract base — standardized responses and permissions
│   │       ├── AdminController.php      # Admin menu, script enqueuing, plugin action links
│   │       ├── AltTextController.php    # REST: generate, apply, batch-generate alt text
│   │       ├── MediaController.php      # REST: media library browsing, scanning, type groups
│   │       ├── OptionsController.php    # REST: read/update plugin settings
│   │       ├── ComplianceController.php # REST: compliance summary, scan, set category
│   │       └── UsageController.php      # REST: current-month API usage statistics
│   ├── Providers/
│   │   ├── ApiProvider.php              # Wires all REST controllers, registers routes
│   │   └── AltTextProvider.php          # Registers Action Scheduler hooks for async batches
│   └── Services/
│       ├── Settings.php                 # Centralized settings management (wp_options)
│       ├── MediaScanner.php             # Media library queries, pagination, filtering
│       ├── AltTextApiService.php        # Facade for alt text generation/application lifecycle
│       ├── AsyncJobService.php          # Background batch scheduling via Action Scheduler
│       ├── ActionSchedulerService.php   # Action Scheduler library initialization
│       ├── ComplianceScanService.php    # Alt text classification and compliance scoring
│       ├── UsageTracker.php             # Per-request API usage tracking and cost estimation
│       ├── WooCommerceHelper.php        # WooCommerce detection and product image lookups
│       ├── Vision/
│       │   ├── VisionProviderInterface.php  # Contract for all vision providers
│       │   ├── VisionProviderFactory.php    # Factory: selects provider based on settings
│       │   └── NoConfigVisionProvider.php   # Fallback when no provider is configured
│       ├── OpenAIService.php            # OpenAI vision provider (gpt-4o-mini)
│       ├── OpenAIApiClient.php          # Low-level HTTP client for OpenAI API
│       ├── GeminiService.php            # Google Gemini vision provider (gemini-2.5-flash-lite)
│       ├── GeminiApiClient.php          # Low-level HTTP client for Gemini API
│       ├── ClaudeService.php            # Anthropic Claude vision provider (claude-haiku-4-5)
│       ├── ClaudeApiClient.php          # Low-level HTTP client for Claude API
│       └── NoOpVisionProvider.php       # Error fallback for unconfigured providers
│
├── assets/                        # Frontend source and build output
│   └── js/
│       ├── src/                   # React application source
│       │   ├── admin/
│       │   │   ├── index.js       # Entry point — mounts React app, extension registry
│       │   │   └── index.html     # HTML template for webpack
│       │   ├── App.js             # Root component — HashRouter, tab navigation, routes
│       │   ├── pages/
│       │   │   ├── OverviewPage.js     # Dashboard with usage stats and compliance summary
│       │   │   ├── MediaPage.js        # Media library with batch processing and alt text editing
│       │   │   ├── CompliancePage.js   # Compliance audit dashboard
│       │   │   └── SettingsPage.js     # Plugin settings (provider, API keys, options)
│       │   ├── components/
│       │   │   ├── index.js            # Barrel export
│       │   │   ├── UpgradeToProCard.js # Pro upgrade prompt
│       │   │   └── common/
│       │   │       ├── ErrorBoundary.js       # React error boundary
│       │   │       └── FluxAIMediaAltIcon.js  # Plugin brand icon
│       │   ├── hooks/
│       │   │   ├── useAltText.js   # React Query hooks for alt text generation
│       │   │   ├── useCompliance.js # React Query hooks for compliance scan
│       │   │   ├── useMedia.js     # React Query hooks for media library
│       │   │   ├── useOptions.js   # React Query hooks for settings
│       │   │   └── useUsage.js     # React Query hooks for usage stats
│       │   └── services/
│       │       └── api.js          # Base API service (@wordpress/api-fetch)
│       └── dist/                  # Webpack build output (gitignored)
│
├── src/                           # Shared common library assets (copied by Composer)
│   └── assets/
│       └── common/                # Assets from flux-plugins-common
│           ├── images/
│           └── js/
│               ├── src/           # Common library React source
│               │   ├── admin/     # License page, logs page, compatibility-dismiss entries
│               │   ├── components/ # FluxAppProvider, LicensePage, LogsPage, PageLayout
│               │   ├── hooks/     # useLicense
│               │   ├── services/  # licenseApi, logsApi
│               │   └── theme/     # Shared MUI theme
│               └── dist/          # Pre-built common library bundles
│
├── tests/                         # Test files
│   └── regression/
│       ├── phase1.smoke.spec.ts        # Playwright: smoke test (login, page load, content)
│       └── phase1.admin-tabs.spec.ts   # Playwright: tab navigation regression test
│
├── vendor/                        # Composer dependencies (gitignored)
└── vendor-prefixed/               # Strauss-prefixed dependencies (gitignored)
    └── stratease/
        └── flux-plugins-common/   # Common library (account ID, licensing, menus, logging, REST)
```

### Backend Architecture

#### Service Layer

The plugin follows a **service-oriented architecture** with singletons for shared state:

- **`Plugin.php`** — Orchestrator that initializes all services and wires them to providers/controllers.
- **`Settings`** — Reads/writes plugin settings from `flux_ai_alt_creator_settings` in `wp_options`. Stores API keys, active provider, and feature flags.
- **`AltTextApiService`** (singleton, facade) — Central orchestrator for alt text generation and application. Delegates to `VisionProviderFactory`, manages scan status lifecycle (`pending` → `processing` → `completed`/`error`), sanitizes AI output, and appends WooCommerce/parent context to prompts. Provides filter hooks for Pro plugin interception.
- **`MediaScanner`** (singleton) — Queries the WordPress media library via `WP_Query`. Supports pagination, search, MIME-type filtering, compliance category filtering, and WooCommerce product image filtering.
- **`ComplianceScanService`** (singleton) — Classifies each attachment's alt text into 6 categories: `missing`, `placeholder`, `duplicate`, `descriptive`, `contextual`, `decorative`. Computes coverage scores and per-category counts.
- **`AsyncJobService`** (singleton) — Splits media IDs into configurable batch sizes and schedules background processing via Action Scheduler.
- **`UsageTracker`** (singleton) — Tracks per-request API usage (tokens, cost, model) in `wp_options` with automatic monthly reset.
- **`WooCommerceHelper`** — Static utility for WooCommerce detection and product image lookups.

#### Vision Provider Layer (Strategy + Factory Pattern)

All AI providers implement `VisionProviderInterface` and are resolved by `VisionProviderFactory`:

| Provider | Service Class | API Client | Model | Pricing (per 1M tokens) |
|---|---|---|---|---|
| OpenAI | `OpenAIService` | `OpenAIApiClient` | `gpt-4o-mini` | $0.15 input / $0.60 output |
| Google Gemini | `GeminiService` | `GeminiApiClient` | `gemini-2.5-flash-lite` | $0.10 input / $0.40 output |
| Anthropic Claude | `ClaudeService` | `ClaudeApiClient` | `claude-haiku-4-5` | $0.80 input / $4.00 output |

Fallback providers (`NoConfigVisionProvider`, `NoOpVisionProvider`) return descriptive errors when no API key is configured.

#### Controller Layer (REST API)

All controllers extend `BaseController` (which extends `WP_REST_Controller`) and register routes under `flux-ai-media-alt-creator/v1/`:

| Controller | Key Routes | Purpose |
|---|---|---|
| `AltTextController` | `POST /alt-text/generate`, `POST /alt-text/apply`, `POST /alt-text/batch-generate` | Generate and apply AI alt text |
| `MediaController` | `GET /media`, `GET /media/{id}`, `POST /media/scan`, `GET /media/type-groups` | Browse and scan the media library |
| `OptionsController` | `GET /options`, `POST /options`, `GET /field-visibility` | Read/update plugin settings |
| `ComplianceController` | `GET /compliance/summary`, `POST /compliance/scan`, `POST /compliance/set-category` | Compliance audit operations |
| `UsageController` | `GET /usage` | Current-month API usage stats |

#### Provider Layer (Boot Hooks)

- **`ApiProvider`** — Instantiates all controllers, registers REST routes on `rest_api_init`, hooks compliance auto-reclassify on alt text changes.
- **`AltTextProvider`** — Registers Action Scheduler callbacks for async batch generation and application.

#### Shared Common Library (`flux-plugins-common`)

A Strauss-prefixed Composer package providing cross-plugin functionality:

- **`FluxPlugins`** — Initialization (account ID, menu setup, REST routes)
- **`MenuService`** — WordPress admin menu registration under "Flux Suite"
- **`RestApiService`** — Common REST API route registration
- **`LicenseService`** — License activation and validation
- **`Logger`** — Database-backed logging with `DatabaseHandler`
- **`CompatibilityService`** — PHP/WordPress version compatibility checks

### Frontend Architecture

#### Tech Stack

- **React 18** with HashRouter (`react-router-dom` v7)
- **Material UI (MUI) v5** with Emotion for styling
- **TanStack React Query v5** for server state management
- **WordPress packages**: `@wordpress/api-fetch`, `@wordpress/i18n`, `@wordpress/components`, `@wordpress/data`
- **Webpack 5** for bundling with Babel transpilation

#### Routing (defined in `App.js`)

| Route | Page Component | Description |
|---|---|---|
| `/overview` (default) | `OverviewPage` | Dashboard with usage statistics and compliance summary |
| `/media` | `MediaPage` | Media library management with batch operations |
| `/compliance` | `CompliancePage` | Alt text compliance audit and category management |
| `/settings` | `SettingsPage` | Provider selection, API keys, and configuration |

Additional tabs can be injected dynamically by the Pro plugin via the `FLUX_EXTENSIONS` registry.

#### Data Layer

Custom hooks in `hooks/` use React Query to communicate with the REST API:

- `useAltText` — Alt text generation and application mutations
- `useMedia` — Media library queries with pagination and filtering
- `useCompliance` — Compliance scan queries and category mutations
- `useOptions` — Settings queries and updates
- `useUsage` — Usage statistics queries

All API calls go through `services/api.js`, which wraps `@wordpress/api-fetch`.

#### Build Output

- `assets/js/dist/admin.bundle.js` — Main plugin admin SPA
- `src/assets/common/js/dist/` — Pre-built common library bundles (license page, logs page, compatibility dismiss)

### Data Flow

```
React SPA (admin.bundle.js)
    │
    ▼  REST API (flux-ai-media-alt-creator/v1/*)
┌──────────────────────────────────────────────────────────────┐
│  Controllers                                                 │
│  AltTextController ── MediaController ── OptionsController   │
│  ComplianceController ── UsageController                     │
│       ▲ extends BaseController                               │
└──────────────┬───────────────────────────────────────────────┘
               │
   ┌───────────┼──────────────────────────┐
   ▼           ▼                          ▼
AltTextApiService    MediaScanner    ComplianceScanService
(facade/orchestrator)  (WP_Query)      (classifier)
   │
   ▼
VisionProviderFactory
   ├── OpenAIService  → OpenAIApiClient  → api.openai.com
   ├── GeminiService  → GeminiApiClient  → generativelanguage.googleapis.com
   ├── ClaudeService  → ClaudeApiClient  → api.anthropic.com
   └── NoConfig/NoOp  → error fallbacks
               │
               ▼
         UsageTracker (wp_options)

AsyncJobService ──► Action Scheduler (background batches)
```

### Key Design Patterns

- **Singleton**: `AltTextApiService`, `AsyncJobService`, `MediaScanner`, `UsageTracker`, `ComplianceScanService`, `OpenAIService` — each accessed via `get_instance()`
- **Strategy + Factory**: `VisionProviderInterface` / `VisionProviderFactory` selects the AI provider at runtime
- **Facade**: `AltTextApiService` is the central orchestrator — controllers never call vision providers directly
- **Service Provider**: `ApiProvider` and `AltTextProvider` bootstrap controllers and hooks during initialization
- **Filter-based extensibility**: Pro plugin intercepts generation/application via `apply_filters()` hooks in `AltTextApiService`
- **Strauss namespace prefixing**: Common library dependencies are prefixed under `FluxAIMediaAltCreator\` to avoid conflicts

## Development Workflow

1. **Create a branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes:**
   - Write clean, documented code
   - Follow the code standards above
   - Add tests if applicable

3. **Build and test the frontend:**
   ```bash
   npm run build
   ```

4. **Run quality checks:**
   ```bash
   composer run quality
   ```

5. **Commit your changes:**
   ```bash
   git commit -m "Your commit message"
   ```

6. **Push to your fork:**
   ```bash
   git push origin feature/your-feature-name
   ```

7. **Create a Pull Request** on GitHub with a clear description

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

By contributing to Flux AI Alt Text & Accessibility Audit, you agree that your contributions will be licensed under the GPL-2.0-or-later license, same as the plugin itself.
