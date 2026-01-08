=== WP Minimal Consent (GTM + Consent Mode v2) ===
Contributors: rociobenitezgarcia
Tags: cookies, consent, privacy, google tag manager, gtm, consent mode, analytics
Requires at least: 5.9
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimal cookie banner for Google Tag Manager with Google Consent Mode v2 (Advanced Mode). Stores consent in a first-party cookie and updates Consent Mode signals.

== Description ==

WP Minimal Consent is a lightweight Consent Management Platform (CMP) for WordPress sites that use Google Tag Manager (GTM) and need a correct Google Consent Mode v2 implementation.

The plugin implements **Advanced Mode**:

1) It sets Consent Mode defaults to `denied` **before GTM loads**.
2) It loads GTM (when a valid Container ID is configured).
3) It shows a minimal banner and a preferences modal.
4) When the user makes a choice, it stores it in a **first-party cookie** and updates Consent Mode.

All analytics/marketing providers (GA4, Google Ads, Meta, etc.) should be managed inside GTM.

= What This Plugin Does =

* Executes `gtag('consent','default', ...)` with `ad_storage`, `analytics_storage`, `ad_user_data`, and `ad_personalization` set to `denied`
* Applies `wait_for_update` to reduce the risk of tags firing before consent is updated
* Stores consent in a first-party cookie (`wpmc_consent` by default; 1 year)
* Updates Consent Mode via `dataLayer.push(['consent','update', ...])`
* Provides a small JavaScript API to read/write consent from custom code
* Optionally prints *blocked* third-party scripts (for cases not managed in GTM) as `<script type="text/plain" data-wpmc-category="...">` and activates them after consent

= What This Plugin Does Not Do =

* It does not inject analytics/ads providers by default
* It does not perform region/geo detection (the default `denied` applies to all visitors)
* It does not store user consent in the WordPress database

= Key Features =

* Google Consent Mode v2 (Advanced Mode)
* GTM loaded after defaults are set
* First-party consent cookie
* Minimal banner + preferences modal (Analytics / Ads). Necessary is always enabled.
* Floating “Preferences” button after the first choice
* Lightweight Settings API page (Settings → WP Minimal Consent)
* Optional debug logs
* No external dependencies

== Installation ==

= Automatic Installation =

1. Go to Plugins → Add New
2. Search for "WP Minimal Consent"
3. Install and activate

= Manual Installation =

1. Upload the folder `wp-minimal-consent` to `/wp-content/plugins/`
2. Activate the plugin via Plugins

= Required Configuration =

1. Go to Settings → WP Minimal Consent
2. Set your GTM Container ID (example: `GTM-ABCDE12`)
3. (Optional) Adjust policy version, `wait_for_update`, texts, and debug mode

= Google Tag Manager Setup =

1. Enable **Consent Mode (Advanced)** in your GTM container
2. Configure your tags to respect consent signals:
   - Analytics tags should require `analytics_storage`
   - Ads tags should require `ad_storage` / `ad_user_data` / `ad_personalization`

== Frequently Asked Questions ==

= Where is the user's consent stored? =

Consent is stored in a first-party cookie in the user's browser.

The plugin configuration (GTM ID, texts, debug, etc.) is stored in the WordPress options table under `wpmc_options`, but **individual user consent is not stored server-side**.

= Does it add the GTM noscript snippet? =

No. This plugin outputs the standard GTM JavaScript loader in the page head.
If you need the GTM `<noscript>` iframe, add it in your theme/template.

= Can I customize texts and behavior? =

Yes. Go to Settings → WP Minimal Consent to change the main banner texts, policy version, `wait_for_update`, and debug mode.

For styling, you can override the CSS from your theme.

= Is the banner responsive? =

Yes. The layout switches from horizontal to vertical on small screens (around 640px).

= Is there a JavaScript API? =

Yes. The plugin exposes:

* `window.RcConsent.getConsent()`
* `window.RcConsent.setConsent({ analytics: true, ads: false, ... })`
* `window.RcConsent.hasConsent('analytics')`
* `window.wpmcCanRun('analytics' | 'ads' | 'functional' | 'necessary')`

Note: the built-in preferences UI currently lets users toggle **Analytics** and **Ads**.

= Can I block third-party scripts that are not managed in GTM? =

Yes. You can return providers through the `wpmc_providers` filter. The plugin will print them as blocked `<script type="text/plain" ...>` and will activate them after consent.

== Screenshots ==

1. Initial cookie banner (desktop)
2. Responsive banner on mobile
3. Preferences panel (modal)
4. Floating “Preferences” button after the first choice

== Changelog ==

= 0.1.1 =
* Initial public release
* Consent Mode v2 defaults (`denied`) + updates on user choice
* Minimal banner + preferences modal
* Settings page (GTM ID, texts, policy version, debug)
* Optional provider gating via `wpmc_providers`

== Upgrade Notice ==

= 0.1.1 =
Initial release. Configure your GTM Container ID after activation.

== Developer Notes ==

= Architecture (Hooks) =

The plugin uses standard WordPress hooks:

* `wp_head` (priority 0) — prints Consent Mode defaults and the GTM loader
* `wp_head` (priority 1) — prints optional blocked providers (`wpmc_providers`)
* `wp_enqueue_scripts` (priority 20) — enqueues banner CSS/JS and injects `window.WPMC_CONFIG`
* `wp_footer` (priority 9999) — prints the banner and preferences modal markup
* `admin_notices` — warns when GTM ID is not configured

= Debugging =

Enable Debug in Settings → WP Minimal Consent and inspect:

* `dataLayer` pushes (`['consent','update', ...]`)
* Cookie value stored under the configured cookie name
* Tag behavior in GTM Preview mode
