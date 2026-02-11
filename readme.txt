=== Flux AI Media Alt Creator by Flux Plugins ===
Contributors: edaniels
Tags: media, alt text, ai, images, seo
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate AI-powered alt text for your WordPress media using OpenAI's Vision API. Process batches of images at once. Improve accessibility, SEO, and WCAG compliance. 

== Description ==

### AI-Powered Alt Text for WordPress Media

Flux AI Media Alt Creator generates descriptive, SEO-friendly alt text for your WordPress media using OpenAI's GPT-4o-mini Vision API. Select multiple images, process them in a batch, then review the AI-generated recommendations. Edit any suggestion before saving, or apply as-is with one click. Transform accessibility compliance from a time-consuming task into a streamlined workflow. Bring your own OpenAI API key for the biggest cost savings.

**Batch processing:** Select multiple media files and generate alt text for all of them at once. Results appear as recommendations you can edit or apply.

**This plugin integrates with OpenAI's Vision API** to analyze your images and generate accurate, descriptive alt text. When you generate alt text, image data is sent to OpenAI's servers for processing using their GPT-4o-mini Vision API endpoint, which analyzes image content and generates descriptive text based on what it sees in your images.

**OpenAI API key required.** The plugin requires an OpenAI API key to function. Image data will be sent to OpenAI's servers for processing when generating alt text. You can [get an OpenAI API key here](https://platform.openai.com/api-keys) (signup required). Please review OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use) before using this plugin.

Looking for automated alt text generation without managing OpenAI API keys? Check out [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/), which includes automation features and only requires a Flux Suite license - no OpenAI API key needed.

### Professional-Grade AI Alt Text Generation

**Key features:**

* **Batch processing** – Process multiple images at once with background job processing
* **Edit before apply** – Review, edit, or apply each recommendation as-is
* **Smart scanning** – Identify media files missing alt text across your library
* **OpenAI Vision API** – Uses GPT-4o-mini for accurate, context-aware descriptions

**Perfect for:** Site owners improving accessibility, content creators with large media libraries, SEO-focused sites, and anyone meeting WCAG guidelines.

### How it works

1. **Scan** – Identify images without alt text in your media library
2. **Select & generate** – Choose one or many images; the plugin sends them to OpenAI's Vision API for analysis
3. **Review** – See AI-generated alt text for each image
4. **Edit or apply** – Adjust any recommendation, then apply individually or in bulk

Pricing is usage-based and shown in the Overview tab. The GPT-4o-mini model is cost-effective for large libraries. Usage tracking is informational only—no feature restrictions.

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, an OpenAI API key is **required** to use this plugin. The plugin integrates with OpenAI's Vision API to analyze images and generate alt text. The plugin cannot function without an OpenAI API key. You can [sign up for an OpenAI account and get an API key here](https://platform.openai.com/api-keys).

If you prefer not to manage API keys, consider [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/), which includes automation features and only requires a single Flux Suite license - no OpenAI API key needed.

= How does the OpenAI integration work? =

The plugin sends image data to OpenAI's Vision API (GPT-4o-mini) for analysis. The AI generates alt text recommendations, which you can edit or apply as-is. Image data is transmitted to OpenAI's servers during generation. Review OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use).

= Will my images be sent to a third party? =

Yes. When you generate alt text, image data is sent to OpenAI's servers via their Vision API for analysis. Data is only sent when you explicitly request generation—no automatic background transmission. Review OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use).

= How much does it cost? =

The plugin uses OpenAI's GPT-4o-mini Vision API, which is very affordable. Pricing is based on usage and displayed in real-time in the Overview tab. You'll be charged by OpenAI based on their pricing for the GPT-4o-mini Vision API. Costs are typically minimal for alt text generation tasks.

**Important:** The plugin's usage tracking and cost estimation features are provided for your information only. They do not limit or restrict any features or functionality - you can use all plugin features without any usage-based restrictions or quotas enforced by the plugin.

= Is there a Pro version with automation? =

Yes! [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/) includes automated alt text generation features and doesn't require an OpenAI API key - all you need is a Flux Suite license. The Pro version automatically processes new media uploads and can schedule recurring processing of existing media. [Learn more about Flux Suite licenses here](https://fluxplugins.com/).

= Does this work with existing images? =

Yes! Scan your entire media library to find images without alt text. Select one or many images, process them in batches (default 10 items per batch via Action Scheduler), then review, edit if needed, and apply the recommendations.

= What image formats are supported? =

The plugin supports all standard WordPress image formats, including JPEG, PNG, GIF, WebP, AVIF, SVG, BMP, TIFF, and ICO. The OpenAI Vision API can analyze any image format supported by WordPress.

== Screenshots ==

1. Overview dashboard displaying usage statistics and Open AI API cost tracking (informational only - no feature restrictions)
2. Media interface with batch processing, scan results, and editable alt text recommendations you can apply as-is or customize

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/flux-ai-media-alt-creator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Flux Suite > AI Media Alt Creator in your WordPress admin.
4. Enter your OpenAI API key in the Settings tab. [Get your OpenAI API key here](https://platform.openai.com/api-keys) if you don't have one.
5. Scan your media, select images, generate alt text in batches, then edit or apply the results.

**Important:** When you generate alt text, image data will be sent to OpenAI's servers via their Vision API for processing. Please ensure you're comfortable with this data sharing and have reviewed OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use) before using the plugin.

== Changelog ==

= 1.2.1 =
* Fixed admin notice display positioning.
* Refactored to a factory service for alt text generators.
* Some UI clean up and optimization for new users.

= 1.2.0 =
* Updated architecture for better Pro plugin integration.
* Fixed selected media assets not utilizing Pro plugin when active.

= 1.1.2 =
* Fixed extra quotes around Open AI response.


== Upgrade Notice ==

= 1.1.0 =
Update includes performance improvements, better batch processing efficiency, and enhanced compatibility with the Pro plugin. The scan status field has been renamed for clarity, but existing data remains compatible.

= 1.0.0 =
Initial release of Flux AI Media Alt Creator. Automatically generate AI-powered alt text for your WordPress media files using OpenAI's Vision API to improve accessibility and SEO.

== Privacy ==

**External Service Integration:**

This plugin integrates with OpenAI's Vision API service to analyze images and generate alt text. This integration is required for the plugin to function.

**What Data is Sent:**

When you generate alt text, the following data is sent to OpenAI's servers:
* Image files (image data is transmitted for analysis via OpenAI's Vision API)
* Image URLs and metadata necessary for processing
* Your OpenAI API key for authentication (stored securely in WordPress options, transmitted only during API requests)

**When Data is Sent:**

Data is only sent when:
* You explicitly request alt text generation for media files
* The plugin makes API calls to OpenAI's Vision API endpoint for image analysis

**Service Provider:**

The external service is provided by OpenAI:
* **Terms of Service**: https://openai.com/policies/terms-of-use
* **Privacy Policy**: https://openai.com/policies/privacy-policy
* **API Documentation**: https://platform.openai.com/docs/guides/vision

**Important Notes:**
* OpenAI integration is required for plugin functionality - the plugin cannot generate alt text without it
* Image data is transmitted to OpenAI's servers for processing via their Vision API
* Your OpenAI API key is stored in WordPress options (encrypted at rest if your WordPress installation supports it)
* You can remove your API key at any time to disable the integration
* No data is automatically sent in the background - transmission only occurs when you explicitly request alt text generation

== Privacy Policy ==

Flux AI Media Alt Creator integrates with OpenAI's Vision API to analyze images and generate alt text. When you use this plugin, image data is sent to OpenAI's servers for processing.

**View our full privacy policy**: [https://fluxplugins.com/privacy-policy/](https://fluxplugins.com/privacy-policy/)

**Key points:**
* OpenAI Vision API integration required for functionality
* Image data transmitted to OpenAI for analysis when generating alt text
* OpenAI API key stored in WordPress options for authentication
* No automatic background transmission - data only sent when you explicitly request alt text generation
* Full compliance with WordPress.org guidelines and privacy regulations

== Developer Notes ==

This plugin follows WordPress coding standards and community best practices. For detailed information on contributing, development setup, coding standards, and architecture, please see the [Contributing Guide](https://github.com/stratease/flux-ai-media-alt-creator/blob/master/CONTRIBUTING.md) on GitHub.
