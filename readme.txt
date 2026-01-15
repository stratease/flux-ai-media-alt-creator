=== Flux AI Media Alt Creator by Flux Plugins ===
Contributors: fluxplugins
Tags: images, alt text, accessibility, ai, openai, seo
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate AI-powered alt text for media files using OpenAI's GPT-4o-mini.

== Description ==

Flux AI Media Alt Creator helps you automatically generate descriptive alt text for your WordPress media files using OpenAI's GPT-4o-mini API. This improves accessibility and SEO for your website.

**Important:** This plugin requires an OpenAI API key to function. Image data will be sent to OpenAI's servers for processing. You can [get an OpenAI API key here](https://platform.openai.com/api-keys) (signup required).

Looking for automated alt text generation without managing API keys? Check out [Flux AI Media Alt Text Generator Pro](https://fluxplugins.com/pro-ai-media-alt-text-generator/), which includes automation features and only requires a Flux Suite license - no OpenAI API key needed.

= Features =

* Scan for media files without alt text
* Generate AI-powered alt text recommendations using OpenAI's GPT-4o-mini
* Bulk process multiple media files
* Background processing with async jobs
* Usage tracking and cost estimation
* Easy-to-use React-based admin interface

= Requirements =

* WordPress 6.0 or higher
* PHP 8.0 or higher
* **OpenAI API key (required)** - [Get your API key here](https://platform.openai.com/api-keys)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/flux-ai-media-alt-creator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Flux Suite > AI Media Alt Creator in your WordPress admin.
4. Enter your OpenAI API key in the Settings tab. [Get your OpenAI API key here](https://platform.openai.com/api-keys) if you don't have one.
5. Start generating alt text for your media files!

**Note:** When you generate alt text, image data will be sent to OpenAI's servers for processing. Please ensure you're comfortable with this data sharing before using the plugin.

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, an OpenAI API key is **required** to use this plugin. The plugin cannot function without it. You can [sign up for an OpenAI account and get an API key here](https://platform.openai.com/api-keys).

If you prefer not to manage API keys, consider [Flux AI Media Alt Text Generator Pro](https://fluxplugins.com/pro-ai-media-alt-text-generator/), which includes automation features and only requires a Flux Suite license - no OpenAI API key needed.

= Will my images be sent to a third party? =

Yes. When you generate alt text using this plugin, image data is sent to OpenAI's servers for processing. This is necessary for the AI to analyze your images and generate descriptive alt text. Please review OpenAI's [Privacy Policy](https://openai.com/policies/privacy-policy) and [Terms of Use](https://openai.com/policies/terms-of-use) before using this plugin.

= How much does it cost? =

The plugin uses OpenAI's GPT-4o-mini model, which is very affordable. Pricing is based on usage and is displayed in the Overview tab. You'll be charged by OpenAI based on their pricing for the GPT-4o-mini API.

= Can I process media files in the background? =

Yes! The plugin supports background processing using Action Scheduler for batch operations.

= Is there a Pro version with automation? =

Yes! [Flux AI Media Alt Text Generator Pro](https://fluxplugins.com/pro-ai-media-alt-text-generator/) includes automated alt text generation features and doesn't require an OpenAI API key - all you need is a Flux Suite license. [Learn more about Flux Suite licenses here](https://fluxplugins.com/).

== Changelog ==

= 1.0.0 =
* Initial release
* Media file scanning functionality
* AI alt text generation using OpenAI GPT-4o-mini
* Usage tracking and cost estimation
* React-based admin interface
* Background processing support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Flux AI Media Alt Creator.

