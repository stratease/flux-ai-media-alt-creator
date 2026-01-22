=== Flux AI Media Alt Creator by Flux Plugins ===
Contributors: edaniels
Tags: media, alt text, ai, images, seo
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate AI-powered alt text for your WordPress media using OpenAI's Vision API. Improve accessibility, SEO, and WCAG compliance.

== Description ==

### The Complete AI-Powered Alt Text Solution for WordPress

Flux AI Media Alt Creator automatically generates descriptive, SEO-friendly alt text for your WordPress media files using OpenAI's GPT-4o-mini Vision API. Transform accessibility compliance from a time-consuming task into an automated process while improving your site's SEO performance.

**This plugin integrates with OpenAI's Vision API** to analyze your images and generate accurate, descriptive alt text. When you generate alt text, image data is sent to OpenAI's servers for processing using their GPT-4o-mini Vision API endpoint, which analyzes image content and generates descriptive text based on what it sees in your images.

**OpenAI API key required.** The plugin requires an OpenAI API key to function. Image data will be sent to OpenAI's servers for processing when generating alt text. You can [get an OpenAI API key here](https://platform.openai.com/api-keys) (signup required). Please review OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use) before using this plugin.

Looking for automated alt text generation without managing API keys? Check out [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/), which includes automation features and only requires a Flux Suite license - no OpenAI API key needed.

### Professional-Grade AI Alt Text Generation

Flux AI Media Alt Creator helps you meet WCAG accessibility guidelines and improve SEO by automatically generating descriptive alt text for your media files. The plugin uses OpenAI's advanced Vision API to analyze image content and create accurate, context-aware descriptions.

**Key Features:**

* **OpenAI Vision API Integration** – Uses OpenAI's GPT-4o-mini Vision API to analyze image content and generate accurate alt text based on what the AI sees in your images
* **Intelligent Image Analysis** – The plugin sends image data to OpenAI's Vision API endpoint, which processes and analyzes visual content to create descriptive, context-aware alt text
* **Bulk Processing** – Process multiple media items with background job processing for efficient large-scale operations
* **Review Before Apply** – Review AI-generated alt text recommendations before applying them to your media files
* **Smart Scanning** – Quickly identify media files missing alt text across your entire media library

**Perfect for:**

* Site owners needing to improve accessibility compliance
* Content creators managing large media libraries
* SEO-focused websites wanting optimized alt text
* Anyone seeking to meet WCAG accessibility guidelines

Ready to automate your alt text generation? Install Flux AI Media Alt Creator today and let AI handle the heavy lifting.

### How OpenAI Vision API Integration Works

Flux AI Media Alt Creator uses **OpenAI's Vision API (GPT-4o-mini)** to analyze your images and generate descriptive alt text. Here's how it works:

1. **Image Upload/Selection** – When you select media files for alt text generation, the plugin retrieves the image URLs from your WordPress media library
2. **OpenAI Vision API Analysis** – Image data is sent to OpenAI's Vision API endpoint, which uses GPT-4o-mini to analyze the visual content of your images
3. **AI-Powered Generation** – The Vision API processes the image, identifies objects, scenes, text, and context, then generates a descriptive alt text recommendation
4. **Review & Apply** – You review the AI-generated recommendations and can apply them individually or in bulk to your media files

The plugin uses OpenAI's `/chat/completions` endpoint with the GPT-4o-mini model, configured specifically for vision content analysis. This ensures accurate, context-aware alt text generation that improves both accessibility and SEO.

### Affordable AI-Powered Accessibility

Flux AI Media Alt Creator uses OpenAI's GPT-4o-mini model, which is very affordable for alt text generation. Pricing is usage-based and displayed in real-time in the plugin's Overview tab. You'll be charged by OpenAI based on their pricing for the GPT-4o-mini Vision API.

**Cost-effective:** The GPT-4o-mini model offers excellent value for alt text generation, making it practical to process large media libraries without breaking the budget.

**Usage Tracking:** The plugin tracks API usage and cost estimates for your information only. This tracking is purely informational and does not limit or restrict any features or functionality - you can generate alt text without any usage-based restrictions or quotas enforced by the plugin.

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, an OpenAI API key is **required** to use this plugin. The plugin integrates with OpenAI's Vision API to analyze images and generate alt text. The plugin cannot function without an OpenAI API key. You can [sign up for an OpenAI account and get an API key here](https://platform.openai.com/api-keys).

If you prefer not to manage API keys, consider [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/), which includes automation features and only requires a single Flux Suite license - no OpenAI API key needed.

= How does the OpenAI integration work? =

The plugin uses OpenAI's Vision API (GPT-4o-mini) to analyze your images. When you generate alt text:

1. The plugin sends image data to OpenAI's Vision API endpoint
2. OpenAI's GPT-4o-mini model analyzes the visual content of your images
3. The AI generates descriptive, context-aware alt text based on what it sees
4. Recommendations are displayed for your review before applying

This integration requires an OpenAI API key and image data will be transmitted to OpenAI's servers for processing. Please review OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use).

= Will my images be sent to a third party? =

Yes. When you generate alt text using this plugin, image data is sent to OpenAI's servers for processing via their Vision API. This is necessary for the AI to analyze your images and generate descriptive alt text. The plugin uses OpenAI's Vision API endpoint to process and analyze visual content.

Please review OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use) before using this plugin. Data is only sent when you explicitly request alt text generation - the plugin does not automatically send images in the background.

= How much does it cost? =

The plugin uses OpenAI's GPT-4o-mini Vision API, which is very affordable. Pricing is based on usage and displayed in real-time in the Overview tab. You'll be charged by OpenAI based on their pricing for the GPT-4o-mini Vision API. Costs are typically minimal for alt text generation tasks.

**Important:** The plugin's usage tracking and cost estimation features are provided for your information only. They do not limit or restrict any features or functionality - you can use all plugin features without any usage-based restrictions or quotas enforced by the plugin.

= Can I process media files in the background? =

Yes! The plugin supports background processing using Action Scheduler for batch operations. This allows you to queue large numbers of media files for processing without blocking your admin interface.

= Is there a Pro version with automation? =

Yes! [Flux AI Media Alt Creator Pro](https://fluxplugins.com/ai-media-alt-creator-pro/) includes automated alt text generation features and doesn't require an OpenAI API key - all you need is a Flux Suite license. The Pro version automatically processes new media uploads and can schedule recurring processing of existing media. [Learn more about Flux Suite licenses here](https://fluxplugins.com/).

= Does this work with existing images? =

Yes! Flux AI Media Alt Creator can scan your entire media library and identify images without alt text. Once you've selected which images to process (individual files or multiple selections), the plugin processes them in batches via Action Scheduler for efficient background processing. Each batch processes a default of 10 items, ensuring your site remains responsive during large-scale operations.

= What image formats are supported? =

The plugin supports all standard WordPress image formats, including JPEG, PNG, GIF, WebP, AVIF, SVG, BMP, TIFF, and ICO. The OpenAI Vision API can analyze any image format supported by WordPress.

== Screenshots ==

1. Modern admin interface showing media library scan results and alt text recommendations
2. Settings page with OpenAI API key configuration
3. Overview dashboard displaying usage statistics and cost tracking (informational only - no feature restrictions)
4. Media processing interface with batch operation controls

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/flux-ai-media-alt-creator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Flux Suite > AI Media Alt Creator in your WordPress admin.
4. Enter your OpenAI API key in the Settings tab. [Get your OpenAI API key here](https://platform.openai.com/api-keys) if you don't have one.
5. Start scanning your media library and generating alt text for your images!

**Important:** When you generate alt text, image data will be sent to OpenAI's servers via their Vision API for processing. Please ensure you're comfortable with this data sharing and have reviewed OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use) before using the plugin.

== Changelog ==

= 1.1.2 =
* Fixed extra quotes around Open AI response.[D[D[D[D[D[D[D[D[D[D[D[D[D

= 1.1.1 =
* Added some escaping, fixed file access warnings, removed artifact file.

= 1.1.0 =
* Cleaned up a lot of infrastructure.
* Setup for better integration with the Pro plugin.


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
