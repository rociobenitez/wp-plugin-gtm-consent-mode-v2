# WP Minimal Consent (GTM + Consent Mode v2)

Minimal **WordPress CMP plugin** that integrates **Google Consent Mode v2** with a lightweight **cookie banner**, **always-on GTM strategy**, and a **developer-first configuration approach**.

The plugin acts as a **Consent Management Layer**, while **all providers (GA4, Ads, Meta, Clarity, etc.) are managed inside Google Tag Manager**.

## Key Features

- Google Consent Mode v2 (`ad_storage`, `analytics_storage`, `ad_user_data`, `ad_personalization`)
- **Advanced Mode**: default `denied` **before GTM loads**
- GTM always loaded (Consent Mode–first strategy)
- Cookie-based consent storage (1st-party cookie)
- Banner + granular preferences panel (Analytics / Marketing)
- Floating “Preferences” button
- Optional debug logs
- No external dependencies

## Requirements

- WordPress 5.9+
- PHP 7.4+
- Google Tag Manager (Web container)
- GTM configured to respect Consent Mode (required)

## Installation

1. Copy the folder `wp-minimal-consent` into `wp-content/plugins/`
2. Activate the plugin in **WP → Plugins**
3. Go to **Settings → WP Minimal Consent**
4. Configure:
   - GTM Container ID
   - Policy version
   - Optional texts and debug mode

## How it works (Architecture)

1. **Before GTM loads**

   - `gtag('consent','default', …)` is executed with all storage types set to `denied`
   - `wait_for_update` is applied

2. **GTM loads normally**

   - GTM is always loaded
   - Tags will not fire unless consent conditions are met

3. **User interaction**

   - Banner stores preferences in a **first-party cookie**
   - Consent Mode is updated via:
     ```
     gtag('consent','update', {...})
     ```
   - A `wpmc_consent_update` event is pushed to `dataLayer`

4. **GTM decides**
   - All providers (GA, Ads, Meta, Clarity…) are controlled in GTM
   - The plugin does **not** inject analytics/ads scripts directly

## Development & Debugging

- Enable **Debug** in plugin settings
- Inspect:
  - `dataLayer` entries
  - Consent updates
  - Network requests (before/after consent)
- Recommended:
  - GTM Preview Mode
  - Chrome DevTools → Application → Cookies

## Roadmap

- Improved accessibility (focus trap)
- i18n support
- Optional iframe placeholders (YouTube / Maps)
- Optional cookie cleanup on revocation
