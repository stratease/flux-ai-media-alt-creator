# Flux AI Alt Text & Accessibility Audit

A WordPress plugin that scans your media library for missing or weak alt text and generates AI-powered replacements using OpenAI, Google Gemini, or Anthropic Claude vision APIs.

**Version:** 3.1.0
**Requires:** WordPress 5.8+ | PHP 8.0+
**Tested up to:** WordPress 6.9
**License:** GPL-2.0-or-later

## Features

### Compliance Dashboard

- On-demand scan of your entire media library
- Alt text coverage score with per-category breakdown
- Images classified into 6 categories: **missing**, **placeholder**, **duplicate**, **descriptive**, **contextual**, **decorative**
- Filter by risk category and fix issues in bulk
- Mark images as decorative (WCAG 2.1 best practice)
- Auto-reclassification when alt text is changed

### AI Alt Text Generation

- Generate descriptive, context-aware alt text using your choice of provider:
  - **OpenAI** (gpt-4o-mini)
  - **Google Gemini** (gemini-2.5-flash-lite)
  - **Anthropic Claude** (claude-haiku-4-5)
- Bulk generation with background processing via Action Scheduler
- Review, edit, and approve recommendations before applying
- Contextual prompts that incorporate post and product data

### WooCommerce Integration

- Automatic detection of product images (featured, gallery, variations)
- Alt text generation includes product name and attributes
- Filter media by WooCommerce product association

### Usage Tracking

- Per-request tracking of API tokens, cost, and model
- Current-month usage dashboard with cost estimates
- Automatic monthly reset

## Installation

### From WordPress.org

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "Flux AI Alt Text"
3. Click **Install Now**, then **Activate**

### Manual Installation

1. Download the plugin ZIP from the [releases page](https://github.com/stratease/flux-ai-media-alt-creator/releases)
2. Upload to `/wp-content/plugins/flux-ai-media-alt-creator/`
3. Activate through the **Plugins** screen in WordPress

### From Source

```bash
git clone https://github.com/stratease/flux-ai-media-alt-creator.git
cd flux-ai-media-alt-creator
composer install
npm install
npm run build
```

## Configuration

1. Navigate to **Flux Suite > AI Media Alt Creator** in your WordPress admin
2. Go to the **Settings** tab
3. Choose your AI provider (OpenAI, Google Gemini, or Anthropic Claude)
4. Enter the API key for your chosen provider:
   - [OpenAI API keys](https://platform.openai.com/settings/organization/api-keys)
   - [Google Gemini API keys](https://aistudio.google.com/apikey)
   - [Anthropic Claude API keys](https://console.anthropic.com/settings/keys)

## Architecture

### Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+, WordPress REST API, Action Scheduler |
| Frontend | React 18, MUI v5, TanStack React Query v5, React Router v7 |
| Build | Webpack 5, Babel, Composer (Strauss for namespace prefixing) |
| Testing | Playwright (E2E regression) |

### Project Structure

```
flux-ai-media-alt-creator/
├── flux-ai-media-alt-creator.php  # Plugin bootstrap
├── app/                           # PHP application (FluxAIMediaAltCreator\App\)
│   ├── Plugin.php                 # Main orchestrator
│   ├── Http/Controllers/          # REST API controllers
│   ├── Providers/                 # Boot hooks (API routes, Action Scheduler)
│   └── Services/                  # Business logic
│       └── Vision/                # AI provider abstraction layer
├── assets/js/src/                 # React SPA source
│   ├── pages/                     # Page components (Overview, Media, Compliance, Settings)
│   ├── hooks/                     # React Query hooks (useAltText, useMedia, etc.)
│   ├── components/                # Shared UI components
│   └── services/                  # API service layer
├── src/assets/common/             # Shared common library assets
├── tests/regression/              # Playwright E2E tests
├── vendor/                        # Composer dependencies
└── vendor-prefixed/               # Strauss-prefixed dependencies
```

### Backend Design

The backend uses a **service-oriented architecture** with singletons for shared state:

- **`AltTextApiService`** is the central facade — controllers never call vision providers directly
- **`VisionProviderFactory`** implements Strategy + Factory to select the AI provider at runtime
- **`ComplianceScanService`** classifies alt text into risk categories
- **`AsyncJobService`** handles background batch processing via Action Scheduler
- Filter hooks throughout `AltTextApiService` enable Pro plugin extensibility

### REST API

All routes are registered under `flux-ai-media-alt-creator/v1/`:

| Endpoint | Method | Description |
|---|---|---|
| `/alt-text/generate` | POST | Generate alt text for a single image |
| `/alt-text/apply` | POST | Apply generated alt text to an image |
| `/alt-text/batch-generate` | POST | Schedule bulk alt text generation |
| `/media` | GET | Paginated media library with filters |
| `/media/{id}` | GET | Single media item details |
| `/media/scan` | POST | Trigger media library scan |
| `/media/type-groups` | GET | Available MIME type groups |
| `/options` | GET/POST | Read/update plugin settings |
| `/field-visibility` | GET | Feature flag visibility |
| `/compliance/summary` | GET | Compliance score and category counts |
| `/compliance/scan` | POST | Run compliance classification scan |
| `/compliance/set-category` | POST | Set compliance category for an image |
| `/usage` | GET | Current-month usage statistics |

### Frontend

A React SPA served on the WordPress admin page with HashRouter navigation:

| Route | Page | Description |
|---|---|---|
| `#/overview` | OverviewPage | Dashboard with usage stats and compliance summary |
| `#/media` | MediaPage | Media library with batch operations |
| `#/compliance` | CompliancePage | Compliance audit and category management |
| `#/settings` | SettingsPage | Provider selection and API key configuration |

## Development

### Prerequisites

- PHP 8.0+
- Composer
- Node.js (LTS)
- A WordPress development environment

### Commands

| Command | Description |
|---|---|
| `composer install` | Install PHP dependencies and prefix namespaces |
| `npm install` | Install Node dependencies |
| `npm run build` | Production webpack build |
| `npm run dev` | Development build with watch mode |
| `npm run start` | Dev server on port 3002 with HMR |
| `composer run phpcs` | Check WordPress coding standards |
| `composer run phpstan` | Run static analysis |
| `composer run test` | Run PHPUnit tests |
| `composer run quality` | Run all quality checks (phpcs + phpstan + tests) |
| `composer run prefix-namespaces` | Re-run Strauss namespace prefixing |
| `npx playwright test` | Run E2E regression tests |

### Namespace Prefixing

This plugin uses [Strauss](https://github.com/BrianHenryIE/strauss) to prefix shared Composer dependencies under the `FluxAIMediaAltCreator\` namespace. This runs automatically via Composer post-install/update hooks. The prefixed output goes to `vendor-prefixed/`.

### Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for coding standards, architecture details, and the pull request process.

## Privacy

When you generate alt text, image data is sent to your chosen AI provider for analysis. Data is only sent when you explicitly request generation — no automatic background transmission. See the [Privacy Policy](https://fluxplugins.com/privacy-policy/) for full details.

## Pro Version

[Flux AI Alt Text & Accessibility Audit Pro](https://fluxplugins.com/ai-media-alt-creator-pro/) adds automated alt text generation on media upload and scheduled recurring processing. No API key management required — only a Flux Suite license.

## Support

- [WordPress.org Support Forum](https://wordpress.org/support/plugin/flux-ai-media-alt-creator/)
- [GitHub Issues](https://github.com/stratease/flux-ai-media-alt-creator/issues)
- [Flux Plugins](https://fluxplugins.com)

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
