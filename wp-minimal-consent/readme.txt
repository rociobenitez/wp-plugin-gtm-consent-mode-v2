=== WP Minimal Consent (GTM + Consent Mode v2) ===
Contributors: rociobenitezgarcia
Tags: cookies, consent, privacy, google tag manager, gtm, consent mode, gdpr, rgpd
Requires at least: 5.9
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimal cookie banner for Google Tag Manager with Google Consent Mode v2 (Advanced Mode). Stores consent in a first-party cookie and updates Consent Mode signals.

== Description ==

WP Minimal Consent is a lightweight Consent Management Platform (CMP) for WordPress sites that use Google Tag Manager (GTM) and need a correct Google Consent Mode v2 implementation.

The plugin implements **Advanced Mode**:

1) Sets Consent Mode defaults to `denied` **before GTM loads**.
2) Loads GTM (when a valid Container ID is configured).
3) Shows a banner (bar or modal) with a granular preferences panel.
4) When the user makes a choice, stores it in a **first-party cookie** and updates Consent Mode signals.

All analytics/marketing providers (GA4, Google Ads, Meta, etc.) are managed inside GTM — this plugin only controls the consent signals.

= What This Plugin Does =

* Executes `gtag('consent','default', ...)` with `ad_storage`, `analytics_storage`, `ad_user_data`, and `ad_personalization` set to `denied` before GTM loads
* Applies `wait_for_update` to reduce the risk of tags firing before consent is updated
* Loads GTM always (Consent Mode–first strategy)
* Optionally injects the GTM `<noscript>` iframe via `wp_body_open` (configurable)
* Stores consent in a first-party cookie (`wpmc_consent` by default; 1 year)
* Updates Consent Mode via `gtag('consent','update', ...)`
* Provides a small JavaScript API to read/write consent from custom code
* Optionally prints *blocked* third-party scripts (for cases not managed in GTM) as `<script type="text/plain" data-wpmc-category="...">` and activates them after consent
* Removes all plugin options on uninstall (does not leave data behind)

= What This Plugin Does Not Do =

* Does not inject analytics/ads providers by default (all providers go through GTM)
* Does not perform region/geo detection (the default `denied` applies to all visitors)
* Does not store individual user consent in the WordPress database

= Key Features =

* Google Consent Mode v2 (Advanced Mode)
* Two banner styles: bar (bottom) and modal (centered, blocking)
* Four consent categories: Necessary (always on), Functional, Analytics, Ads
* Collapsible cookie list per category with name, purpose, duration, and type
* Pre-filled defaults for GA4 and Google Ads cookies
* Floating "Preferences" button after the first choice
* Custom colors for the accept button and banner background
* Lightweight admin panel with card-based layout (main menu: Consent)
* Optional debug logs
* No external dependencies

== Installation ==

= Manual Installation =

1. Upload the folder `wp-minimal-consent` to `/wp-content/plugins/`
2. Activate the plugin via Plugins

= Required Configuration =

1. Go to **Consent → Ajustes** (main admin menu)
2. Set your GTM Container ID (example: `GTM-ABCDE12`)
3. Set your Privacy Policy URL (required by GDPR)
4. (Optional) Choose banner style, adjust colors, edit texts, configure cookie lists

= Google Tag Manager Setup =

1. Enable **Consent Mode (Advanced)** in your GTM container
2. Configure your tags to respect consent signals:
   - Analytics tags → require `analytics_storage`
   - Ads tags → require `ad_storage`, `ad_user_data`, `ad_personalization`

== Frequently Asked Questions ==

= Where is the user's consent stored? =

Consent is stored in a first-party cookie in the user's browser (`wpmc_consent` by default, 1 year expiry).

Plugin configuration (GTM ID, texts, debug, etc.) is stored in the WordPress options table under `wpmc_options`. Individual user consent is never stored server-side.

= Does it add the GTM noscript snippet? =

It depends on the setting. In **Consent → Ajustes** there is a toggle: "El tema incluye el noscript de GTM".

* If **enabled** (default): the plugin assumes your theme already includes the GTM `<noscript>` tag and does not add it.
* If **disabled**: the plugin injects it automatically via `wp_body_open`.

Note: `wp_body_open` requires your theme to call `wp_body_open()` in its template. Block themes (FSE) include this by default.

= Can I customize texts and behavior? =

Yes. Go to **Consent → Ajustes** to change banner texts, banner style, privacy policy URL, custom colors, policy version, `wait_for_update`, cookie lists, and debug mode.

For styling, you can override the CSS from your theme's stylesheet.

= Is the banner responsive? =

Yes. The bar layout switches to a stacked vertical layout on small screens (≤640px). The modal slides up from the bottom on mobile.

= Is there a JavaScript API? =

Yes. The plugin exposes:

* `window.RcConsent.getConsent()` — returns the full consent object or null
* `window.RcConsent.setConsent({ analytics, ads, functional, necessary })` — saves consent and updates the cookie
* `window.RcConsent.hasConsent('analytics')` — returns a boolean
* `window.wpmcCanRun('analytics' | 'ads' | 'functional' | 'necessary')` — checks if a category is allowed

= Can I block third-party scripts not managed in GTM? =

Yes. Use the `wpmc_providers` filter to register scripts. The plugin will print them as blocked `<script type="text/plain" data-wpmc-category="...">` tags and activate them after the user grants consent for the corresponding category.

= What happens when the plugin is deleted? =

The `uninstall.php` file removes the `wpmc_options` entry from the database. No data is left behind.

== Screenshots ==

1. Cookie banner — bar style (desktop)
2. Cookie banner — modal style (desktop)
3. Preferences panel with category toggles and collapsible cookie list
4. Floating "Preferences" button after the first choice
5. Admin settings panel

== Changelog ==

= 0.2.0 =
* New admin UI: card-based layout, main menu entry ("Consent"), custom color pickers for accept button and banner background
* Modal banner style (blocking, with blur overlay) added alongside the existing bar style
* Functional cookie category added (banner, preferences panel, and JS API)
* Collapsible cookie list per category (name, purpose, duration, type, optional URL)
* Pre-filled defaults for GA4 (_ga, _gid, _ga_XXXXXXXXXX) and Google Ads (_gcl_au) with inline guidance notes
* GTM `<noscript>` tag injected via `wp_body_open`, with configurable toggle (theme vs plugin)
* Necessary cookies toggle is now a locked, non-interactive switch (disabled state, not-allowed cursor, tooltip)
* Clean uninstall via `uninstall.php` — removes `wpmc_options` on plugin delete
* Accessibility improvements: focus management, ARIA attributes, keyboard navigation (Escape key closes modal)
* Fixed: CSS cascade issue causing white text on modal banner
* Fixed: color fields not saving correctly

= 0.1.1 =
* Initial public release
* Consent Mode v2 defaults (`denied`) + updates on user choice
* Bar banner + preferences modal (Analytics / Ads)
* Settings page (GTM ID, texts, policy version, debug)
* Optional provider gating via `wpmc_providers` filter

== Upgrade Notice ==

= 0.2.0 =
Existing settings are preserved. After updating, review the new "El tema incluye el noscript de GTM" toggle and check the pre-filled cookie lists. The admin menu has moved to the main sidebar under "Consent".

= 0.1.1 =
Initial release. Configure your GTM Container ID after activation.

== Developer Notes ==

= Architecture (Hooks) =

* `wp_head` (priority 0) — Consent Mode defaults + GTM JavaScript loader
* `wp_head` (priority 1) — Blocked provider scripts via `wpmc_providers`
* `wp_body_open` (priority 0) — GTM `<noscript>` iframe (only if "noscript_by_theme" is disabled)
* `wp_enqueue_scripts` (priority 20) — Banner CSS/JS + inline `window.WPMC_CONFIG`
* `wp_footer` (priority 9999) — Banner HTML and preferences modal markup
* `admin_notices` — Warning when GTM Container ID is not configured

= Blocking external scripts =

Use the `wpmc_providers` filter to register scripts that should be gated by consent but are not managed in GTM:

  add_filter( 'wpmc_providers', function ( $providers ) {
    $providers['my_vendor'] = array(
      'category' => 'analytics',
      'type'     => 'script',
      'src'      => 'https://example.com/vendor.js',
      'attrs'    => array( 'async' => true ),
    );
    return $providers;
  } );

= Debugging =

Enable Debug in **Consent → Ajustes** and inspect:

* `dataLayer` pushes in the browser console (`[WPMC] ...` prefix)
* Cookie value stored under the configured cookie name
* Tag behavior in GTM Preview Mode
* Chrome DevTools → Application → Cookies
