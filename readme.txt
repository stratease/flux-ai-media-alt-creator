=== Flux AI Media Alt Creator by Flux Plugins ===
Contributors: edaniels
Tags: media, alt text, ai, images, seo
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered alt text for WordPress media. Batch process images with OpenAI, Google Gemini, or Anthropic Claude vision APIs. Improves accessibility and SEO.

== Description ==

### AI-Powered Alt Text for WordPress Media

Flux AI Media Alt Creator generates descriptive, SEO-friendly alt text for your WordPress media using your choice of vision API: **OpenAI** (GPT-4o-mini), **Google Gemini** (gemini-2.5-flash-lite), or **Anthropic Claude** (claude-haiku-4-5-20251001). Select multiple images, process them in a batch, then review the AI-generated recommendations. Edit any suggestion before saving, or apply as-is with one click. Transform accessibility compliance from a time-consuming task into a streamlined workflow. Bring your own API key for the provider you choose.

**Batch processing:** Select multiple media files and generate alt text for all of them at once. Results appear as recommendations you can edit or apply.

**Multiple vision providers (2.0.0):** Choose OpenAI, Google Gemini, or Anthropic Claude in Settings. The plugin uses the selected provider's vision API to analyze images and generate alt text. Cost estimates in the Overview tab are calculated correctly for each model.

**API key required.** You need an API key for your chosen provider. Get keys here: [OpenAI](https://platform.openai.com/settings/organization/api-keys), [Google Gemini](https://aistudio.google.com/apikey), [Anthropic Claude](https://console.anthropic.com/settings/keys). Image data is sent to the selected provider when you generate alt text. Please review each provider's privacy and terms before use.

Looking for automated alt text generation without managing API keys? Check out [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/), which includes automation features and only requires a Flux Suite license.

### Professional-Grade AI Alt Text Generation

**Key features:**

* **Batch processing** – Process multiple images at once with background job processing
* **Edit before apply** – Review, edit, or apply each recommendation as-is
* **Smart scanning** – Identify media files missing alt text across your library
* **Multiple vision APIs (2.0.0)** – OpenAI (gpt-4o-mini), Google Gemini (gemini-2.5-flash-lite), or Anthropic Claude (claude-haiku-4-5) for accurate, context-aware descriptions

**Perfect for:** Site owners improving accessibility, content creators with large media libraries, SEO-focused sites, and anyone meeting WCAG guidelines.

### How it works

1. **Scan** – Identify images without alt text in your media library
2. **Select & generate** – Choose one or many images; the plugin sends them to your chosen provider's vision API for analysis
3. **Review** – See AI-generated alt text for each image
4. **Edit or apply** – Adjust any recommendation, then apply individually or in bulk

Pricing is usage-based and shown in the Overview tab. Cost estimates are calculated per model (OpenAI, Gemini, Claude). Usage tracking is informational only—no feature restrictions.

== Frequently Asked Questions ==

= Do I need an API key? =

Yes. You need an API key for the provider you select in Settings: **OpenAI** ([get key](https://platform.openai.com/settings/organization/api-keys)), **Google Gemini** ([get key](https://aistudio.google.com/apikey)), or **Anthropic Claude** ([get key](https://console.anthropic.com/settings/keys)). The plugin uses that provider's vision API to analyze images and generate alt text.

If you prefer not to manage API keys, consider [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/), which includes automation and only requires a Flux Suite license.

= How does the vision API integration work? =

Choose a provider in Settings (OpenAI, Google Gemini, or Anthropic Claude). The plugin sends image data to that provider's vision API for analysis and generates alt text recommendations you can edit or apply. Image data is transmitted only when you request generation. Review each provider's privacy policy and terms before use.

= Will my images be sent to a third party? =

Yes. When you generate alt text, image data is sent to the provider you selected (OpenAI, Google Gemini, or Anthropic Claude) for analysis. Data is only sent when you explicitly request generation—no automatic background transmission. Review each provider's privacy policy and terms before use.

= How much does it cost? =

Cost depends on the provider and model you choose. The Overview tab shows usage and estimated cost; calculations are correct for each model (OpenAI gpt-4o-mini, Google Gemini 2.5 Flash-Lite, Anthropic Claude Haiku 4.5). You are charged by the provider based on their pricing. A tooltip on the Usage Statistics section shows which provider and model are used for the estimates.

**Important:** The plugin's usage tracking and cost estimation are for your information only. They do not limit or restrict any features.

= Is there a Pro version with automation? =

Yes! [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/) includes automated alt text generation features and doesn't require an OpenAI API key - all you need is a Flux Suite license. The Pro version automatically processes new media uploads and can schedule recurring processing of existing media. [Learn more about Flux Suite licenses here](https://fluxplugins.com/).

= Does this work with existing images? =

Yes! Scan your entire media library to find images without alt text. Select one or many images, process them in batches (default 10 items per batch via Action Scheduler), then review, edit if needed, and apply the recommendations.

= What image formats are supported? =

The plugin supports all standard WordPress image formats, including JPEG, PNG, GIF, WebP, AVIF, SVG, BMP, TIFF, and ICO. Supported vision APIs can analyze the image formats they accept (typically JPEG, PNG, GIF, WebP).

== Screenshots ==

1. Overview dashboard displaying usage statistics and Open AI API cost tracking (informational only - no feature restrictions)
2. Media interface with batch processing, scan results, and editable alt text recommendations you can apply as-is or customize

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/flux-ai-media-alt-creator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Flux Suite > AI Media Alt Creator in your WordPress admin.
4. In Settings, choose your AI provider (OpenAI, Google Gemini, or Anthropic Claude) and enter the corresponding API key. Get keys: [OpenAI](https://platform.openai.com/settings/organization/api-keys), [Gemini](https://aistudio.google.com/apikey), [Claude](https://console.anthropic.com/settings/keys).
5. Scan your media, select images, generate alt text in batches, then edit or apply the results.

**Important:** When you generate alt text, image data is sent to your chosen provider for processing. Please review that provider's privacy policy and terms before use.

== Changelog ==

= 2.0.0 =
* Fixed edge case where alt text preview would not update after being edited.
* Added integration for Gemini and Claude API's.

= 2.0.0 =
* **Multiple vision providers:** Added Google Gemini (gemini-2.5-flash-lite) and Anthropic Claude (claude-haiku-4-5) alongside OpenAI (gpt-4o-mini). Choose your provider in Settings.
* Provider-specific API key fields and setup links: OpenAI, [Gemini](https://aistudio.google.com/apikey), [Claude](https://console.anthropic.com/settings/keys).
* Usage Statistics tooltip shows which provider and model are used; cost estimates are calculated correctly for each model.
* Backwards compatible: existing installs continue to use OpenAI by default. Pro plugin integration unchanged.

= 1.2.1 =
* Fixed admin notice display positioning.
* Refactored to a factory service for alt text generators.
* Some UI clean up and optimization for new users.


== Upgrade Notice ==

= 2.0.0 =
Major update: choose OpenAI, Google Gemini, or Anthropic Claude for alt text generation. Existing sites keep using OpenAI by default. Cost estimates and Usage Statistics tooltip reflect the active provider.

= 1.1.0 =
Update includes performance improvements, better batch processing efficiency, and enhanced compatibility with the Pro plugin. The scan status field has been renamed for clarity, but existing data remains compatible.

= 1.0.0 =
Initial release of Flux AI Media Alt Creator. Automatically generate AI-powered alt text for your WordPress media files using OpenAI's Vision API to improve accessibility and SEO.

== Privacy ==

**External Service Integration:**

This plugin can integrate with OpenAI, Google (Gemini), or Anthropic (Claude) vision APIs to analyze images and generate alt text. You choose one provider in Settings. The selected integration is required for the plugin to function.

**What Data is Sent:**

When you generate alt text, the following data is sent to the selected provider's servers:
* Image files or URLs (for analysis via the provider's vision API)
* Your API key for authentication (stored in WordPress options, transmitted only during API requests)

**When Data is Sent:**

Data is only sent when you explicitly request alt text generation. No automatic background transmission.

**Service Providers (one chosen in Settings):**
* **OpenAI** – Terms: https://openai.com/policies/terms-of-use | Privacy: https://openai.com/policies/privacy-policy | API keys: https://platform.openai.com/settings/organization/api-keys
* **Google Gemini** – API keys: https://aistudio.google.com/apikey
* **Anthropic Claude** – API keys: https://console.anthropic.com/settings/keys

**Important Notes:**
* An API key for your chosen provider is required
* Image data is transmitted to that provider when you request generation
* You can remove or change the API key at any time
* No data is sent in the background

== Privacy Policy ==

Flux AI Media Alt Creator integrates with one of OpenAI, Google Gemini, or Anthropic Claude (your choice in Settings) to analyze images and generate alt text. When you generate alt text, image data is sent to the selected provider for processing.

**View our full privacy policy**: [https://fluxplugins.com/privacy-policy/](https://fluxplugins.com/privacy-policy/)

**Key points:**
* One vision API (OpenAI, Gemini, or Claude) is used based on Settings
* Image data is transmitted to that provider when you request generation
* API key stored in WordPress options; no automatic background transmission
* Full compliance with WordPress.org guidelines and privacy regulations

== Developer Notes ==

This plugin follows WordPress coding standards and community best practices. For detailed information on contributing, development setup, coding standards, and architecture, please see the [Contributing Guide](https://github.com/stratease/flux-ai-media-alt-creator/blob/master/CONTRIBUTING.md) on GitHub.
