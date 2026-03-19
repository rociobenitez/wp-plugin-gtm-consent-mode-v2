# WP Minimal Consent

**Minimal WordPress CMP plugin** that integrates **Google Consent Mode v2** with a lightweight cookie banner, always-on GTM strategy, and a developer-first configuration approach.

The plugin acts as a **Consent Management Layer**. All analytics/marketing providers (GA4, Ads, Meta, Clarity, etc.) are managed inside Google Tag Manager — the plugin only controls the consent signals.

## Key Features

- Google Consent Mode v2 (`ad_storage`, `analytics_storage`, `ad_user_data`, `ad_personalization`)
- **Advanced Mode**: defaults set to `denied` before GTM loads
- GTM always loaded (Consent Mode–first strategy)
- GTM `<noscript>` tag injected via `wp_body_open` (configurable: let the theme handle it instead)
- Two banner styles: **bar** (bottom) and **modal** (centered, blocking)
- Cookie-based consent storage (1st-party, 1 year)
- Four consent categories: Necessary (locked), Functional, Analytics, Ads
- Collapsible cookie list per category with name, purpose, duration, and type
- Floating "Preferences" button after the first choice
- Custom colors for accept button and banner background
- Pre-filled defaults for GA4 and Google Ads cookies
- Optional blocked provider scripts via `wpmc_providers` filter
- Optional debug logs
- No external dependencies
- Clean uninstall (removes `wpmc_options` on plugin delete)

## Requirements

- WordPress 5.9+
- PHP 7.4+
- Google Tag Manager (Web container)
- GTM configured to respect Consent Mode signals

## Installation

1. Copy the folder `wp-minimal-consent` into `wp-content/plugins/`
2. Activate the plugin in **WP → Plugins**
3. Go to **Consent → Ajustes** (main admin menu)
4. Configure:
   - GTM Container ID (e.g. `GTM-ABCDE12`)
   - Banner style (bar or modal)
   - Privacy policy URL
   - Custom colors (optional)
   - Cookie lists per category (optional)

## How it works

1. **Before GTM loads** — `gtag('consent','default', …)` runs with all storage types set to `denied` + `wait_for_update`
2. **GTM loads** — always, regardless of consent. Tags fire only when their consent conditions are met
3. **User interacts** — banner stores preferences in a first-party cookie and calls `gtag('consent','update', {...})`
4. **GTM decides** — all providers are controlled inside GTM, not by this plugin

## JavaScript API

```js
window.RcConsent.getConsent()                         // returns full consent object or null
window.RcConsent.setConsent({ analytics, ads, ... })  // saves consent and updates cookie
window.RcConsent.hasConsent('analytics')              // returns boolean
window.wpmcCanRun('analytics' | 'ads' | 'functional' | 'necessary')
```

## Blocking scripts outside GTM

Use the `wpmc_providers` filter to register third-party scripts that are not managed in GTM. The plugin will print them as `<script type="text/plain" data-wpmc-category="...">` and activate them after the user grants consent:

```php
add_filter( 'wpmc_providers', function ( $providers ) {
  $providers['my_vendor'] = array(
    'category' => 'analytics',
    'type'     => 'script',
    'src'      => 'https://example.com/vendor.js',
    'attrs'    => array( 'async' => true ),
  );
  return $providers;
} );
```

## WordPress hooks

| Hook | Priority | Purpose |
|---|---|---|
| `wp_head` | 0 | Consent Mode defaults + GTM loader |
| `wp_head` | 1 | Blocked provider scripts (`wpmc_providers`) |
| `wp_body_open` | 0 | GTM `<noscript>` iframe (if enabled) |
| `wp_enqueue_scripts` | 20 | Banner CSS/JS + `window.WPMC_CONFIG` |
| `wp_footer` | 9999 | Banner and preferences modal markup |
| `admin_notices` | — | Warning when GTM ID is not configured |

## Debug

Enable **Debug** in **Consent → Ajustes** and inspect:

- `dataLayer` pushes in the browser console
- Cookie value stored under the configured cookie name
- Tag behavior in GTM Preview Mode
- Chrome DevTools → Application → Cookies

## Changelog

### 0.2.0
- New admin UI: card layout, main menu entry ("Consent"), custom color pickers
- Modal banner style (blocking) in addition to the existing bar
- Functional cookie category added
- Collapsible cookie list per category (name, purpose, duration, type, URL)
- Pre-filled defaults for GA4 and Google Ads cookies with inline guidance
- GTM `<noscript>` tag via `wp_body_open` (with theme/plugin toggle)
- Necessary toggle locked (disabled, `not-allowed` cursor, tooltip)
- Clean uninstall via `uninstall.php`
- Accessibility improvements: focus management, ARIA attributes, keyboard navigation

### 0.1.1
- Initial release
- Consent Mode v2 defaults + updates on user choice
- Bar banner + preferences modal (Analytics / Ads)
- Settings page (GTM ID, texts, policy version, debug)
- Optional provider gating via `wpmc_providers`

## Roadmap

- i18n / translation support
- Optional iframe placeholders (YouTube, Google Maps)
- Optional cookie cleanup on consent revocation
- Geo-based consent defaults (e.g. granted outside EEA)
