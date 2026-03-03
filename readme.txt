=== Flux AI Alt Text & Accessibility Audit by Flux Plugins ===
Contributors: edaniels
Tags: alt text, accessibility, image seo, ai, wcag, media library, woocommerce
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 3.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Missing or weak alt text hurts SEO rankings and can create accessibility gaps. Scan and fix your entire media library in minutes with the help of AI.

== Description ==

Missing or weak alt text on images hurts both SEO and accessibility. Flux AI Alt Text & Accessibility Audit helps you find and fix those gaps across your entire media library with an on-demand Compliance Audit Dashboard and AI-generated alt text.

= NEW – Compliance Dashboard =

Scan your entire media library on demand. The Compliance Dashboard shows a coverage score and categorizes images by risk: missing alt text, placeholder or generic text, duplicates, and descriptive or contextual alt. Filter by category and fix issues in bulk. Run a full scan to see which images need attention, which are marked decorative, and which already have solid alt text—then generate, apply, or mark decorative in one place.

= AI Alt Text Generation =

Generate descriptive, context-aware alt text in bulk using your choice of **OpenAI**, **Google Gemini**, or **Anthropic Claude**. Review each recommendation before applying. The plugin can use post or product context when available. WooCommerce product images are supported; alt text can include product name and attributes. Bring your own API key for the provider you choose. Get keys: [OpenAI](https://platform.openai.com/settings/organization/api-keys), [Google Gemini](https://aistudio.google.com/apikey), [Anthropic Claude](https://console.anthropic.com/settings/keys).

= Built for Agencies & Site Owners =

Fix hundreds or thousands of images quickly. Reduce accessibility risk exposure and improve media SEO coverage with bulk generation, risk-based filtering, and one-click mark-as-decorative. The plugin aligns with WCAG 2.1 guidance for non-text content; it does not provide legal certification or guarantee full WCAG compliance.

= Works With =

* WooCommerce (product images)
* Any WordPress theme
* Major SEO plugins

Looking for automated alt text without managing API keys? [Flux AI Alt Text & Accessibility Audit Pro](https://fluxplugins.com/ai-media-alt-creator-pro/) includes automation and requires only a Flux Suite license.

= Core Features =

* On-demand media library scan
* Alt Text Coverage Score
* Missing, Placeholder, and Duplicate detection
* AI-generated descriptive alt text
* Bulk generate and apply
* Mark images as decorative (WCAG best practice)
* WooCommerce product image support
* Context-aware generation using post/product data
* Lightweight and WordPress-native

== Frequently Asked Questions ==

= What is the Compliance Dashboard? =

The Compliance Dashboard scans your media library and categorizes alt text into missing, placeholder, duplicate, descriptive, or contextual groups. It helps you identify and fix accessibility and SEO risks quickly.

= Does this plugin guarantee WCAG compliance? =

No plugin can guarantee full WCAG compliance. This plugin helps improve alt text coverage and aligns with WCAG 2.1 guidance for non-text content.

= Can I bulk fix alt text? =

Yes. You can filter by risk category and generate alt text in bulk directly from the plugin's Media tab.

= Does it support WooCommerce products? =

Yes. Product images are detected automatically and alt text can include product name and attributes.

= Which AI models are used? =

The plugin uses one vision model per provider: **OpenAI** (gpt-4o-mini), **Google Gemini** (gemini-2.5-flash-lite), or **Anthropic Claude** (claude-haiku-4-5). You choose the provider in Settings. Cost estimates in the Overview tab are calculated for the active model.

= Do I need an API key? =

Yes. You need an API key for the provider you select in Settings: **OpenAI** ([get key](https://platform.openai.com/settings/organization/api-keys)), **Google Gemini** ([get key](https://aistudio.google.com/apikey)), or **Anthropic Claude** ([get key](https://console.anthropic.com/settings/keys)). The plugin uses that provider's vision API to analyze images and generate alt text.

If you prefer not to manage API keys, consider [Flux AI Alt Text & Accessibility Audit Pro](https://fluxplugins.com/ai-media-alt-creator-pro/), which includes automation and only requires a Flux Suite license.

= How does the vision API integration work? =

Choose a provider in Settings (OpenAI, Google Gemini, or Anthropic Claude). The plugin sends image data to that provider's vision API for analysis and generates alt text recommendations you can edit or apply. Image data is transmitted only when you request generation. Review each provider's privacy policy and terms before use.

= Will my images be sent to a third party? =

Yes. When you generate alt text, image data is sent to the provider you selected (OpenAI, Google Gemini, or Anthropic Claude) for analysis. Data is only sent when you explicitly request generation—no automatic background transmission. Review each provider's privacy policy and terms before use.

= How much does it cost? =

Cost depends on the provider and model you choose. The Overview tab shows usage and estimated cost for the active provider's model. You are charged by the provider based on their pricing. A tooltip on the Usage Statistics section shows which provider and model are used.

**Important:** The plugin's usage tracking and cost estimation are for your information only. They do not limit or restrict any features.

**Important:** The plugin's usage tracking and cost estimation are for your information only. They do not limit or restrict any features.

= Is there a Pro version with automation? =

Yes! [Flux AI Alt Text & Accessibility Audit Pro](https://fluxplugins.com/ai-media-alt-creator-pro/) includes automated alt text generation features and doesn't require an OpenAI API key - all you need is a Flux Suite license. The Pro version automatically processes new media uploads and can schedule recurring processing of existing media. [Learn more about Flux Suite licenses here](https://fluxplugins.com/).

= Does this work with existing images? =

Yes! Scan your entire media library to find images without alt text. Select one or many images, process them in batches (default 10 items per batch via Action Scheduler), then review, edit if needed, and apply the recommendations.

= What image formats are supported? =

The plugin supports all standard WordPress image formats, including JPEG, PNG, GIF, WebP, AVIF, SVG, BMP, TIFF, and ICO. Supported vision APIs can analyze the image formats they accept (typically JPEG, PNG, GIF, WebP).

== Screenshots ==

1. Overview dashboard with usage statistics and compliance scan
2. Media interface with batch processing, scan results, and editable alt text recommendations

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/flux-ai-media-alt-creator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Flux Suite > AI Media Alt Creator in your WordPress admin.
4. In Settings, choose your AI provider (OpenAI, Google Gemini, or Anthropic Claude) and enter the corresponding API key. Get keys: [OpenAI](https://platform.openai.com/settings/organization/api-keys), [Gemini](https://aistudio.google.com/apikey), [Claude](https://console.anthropic.com/settings/keys).
5. Scan your media, select images, generate alt text in batches, then edit or apply the results.

**Important:** When you generate alt text, image data is sent to your chosen provider for processing. Please review that provider's privacy policy and terms before use.

== Changelog ==

= 3.1.0 =
* Reposition and renamed focus of plugin to WCAG Compliance and WooCommerce integration. Now named: Flux AI Alt Text & Accessibility Audit.

= 3.0.0 =
* **Compliance Dashboard (major update):** New dedicated compliance experience to help you meet accessibility and SEO goals.
* **Overview & Compliance pages:** Central dashboard with usage stats, one-click compliance scan, and a dedicated Compliance tab for managing alt text status across your media library.
* **Compliance scan:** Run a full scan to see which images have alt text, which are missing it, and which are marked decorative. Filter by status and fix in bulk.
* **Set category / reclassify:** Mark images as decorative or reclassify after editing alt text. Single set-category API supports both explicit categories and reclassification.
* **Media scanner "All" filter:** View all image attachments with accurate counts; filter by compliance status (e.g. missing, has alt, decorative).
* **Improved Media UI:** Clearer column labels (Alt Text, Proposed Alt Text, Status), icon actions with tooltips for Generate, Apply, Mark Decorative, and Unmark Decorative.
* **Bulk actions:** Generate AI alt text, apply recommendations, and manage decorative status from the Compliance and Media views with reliable bulk handling.
* **Immediate reclassification:** When you apply alt text or change category, compliance state updates right away so the dashboard stays in sync.

= 2.0.0 =
* Fixed edge case where alt text preview would not update after being edited.
* Added integration for Gemini and Claude API's.


== Upgrade Notice ==

= 3.0.0 =
Major update: new Compliance Dashboard to streamline accessibility and SEO. Run compliance scans, see which images need alt text or are decorative, filter and fix in bulk, and keep everything in sync. Overview and Compliance tabs plus improved Media UI make it easier to meet WCAG and SEO goals.

= 2.0.0 =
Major update: choose OpenAI, Google Gemini, or Anthropic Claude for alt text generation. Added integration with Google Gemini and Anthropic Claude. Existing sites keep using OpenAI by default. Cost estimates and Usage Statistics tooltip reflect the active provider.

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

Flux AI Alt Text & Accessibility Audit integrates with one of OpenAI, Google Gemini, or Anthropic Claude (your choice in Settings) to analyze images and generate alt text. When you generate alt text, image data is sent to the selected provider for processing.

**View our full privacy policy**: [https://fluxplugins.com/privacy-policy/](https://fluxplugins.com/privacy-policy/)

**Key points:**
* One vision API (OpenAI, Gemini, or Claude) is used based on Settings
* Image data is transmitted to that provider when you request generation
* API key stored in WordPress options; no automatic background transmission
* Full compliance with WordPress.org guidelines and privacy regulations

== Developer Notes ==

This plugin follows WordPress coding standards and community best practices. For detailed information on contributing, development setup, coding standards, and architecture, please see the [Contributing Guide](https://github.com/stratease/flux-ai-media-alt-creator/blob/master/CONTRIBUTING.md) on GitHub.
