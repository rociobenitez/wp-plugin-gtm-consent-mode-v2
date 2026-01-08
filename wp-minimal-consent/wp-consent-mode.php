<?php
/**
 * Plugin Name: WP Minimal Consent (GTM + Consent Mode v2)
 * Description: Banner de cookies + Google Consent Mode v2. GTM siempre, por defecto "denied", y actualiza según consentimiento.
 * Version: 0.1.1
 * Author: Rocío Benítez García
 * Require at least: 5.9
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-minimal-consent
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Configuración base
 */
define('WPMC_VERSION', '0.1.1');
define('WPMC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPMC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * INCLUDES
 */
require_once WPMC_PLUGIN_DIR . 'includes/config.php';
require_once WPMC_PLUGIN_DIR . 'includes/providers.php';
require_once WPMC_PLUGIN_DIR . 'includes/helpers.php';

/**
 * ADMIN
 */
if ( is_admin() ) {
  require_once WPMC_PLUGIN_DIR . 'admin/wp-consent-mode-admin.php';
}

add_action('admin_notices', function () {
  if (!current_user_can('manage_options')) return;
  if (!WPMC_GTM_ID || WPMC_GTM_ID === 'GTM-XXXXXXX') {
    echo '<div class="notice notice-error"><p><strong>WP Minimal Consent:</strong> configura tu <code>WPMC_GTM_ID</code> en el archivo del plugin.</p></div>';
  }
});

/**
 * FRONTEND ENQUEUE: Banner UI + Lógica de Control
 */
add_action('wp_enqueue_scripts', function () {
  // CSS del banner
  wp_enqueue_style(
    'wpmc-banner-styles',
    plugins_url('public/css/banner.css', __FILE__),
    [],
    '0.1.0'
  );

  // Store (cookies)
  wp_enqueue_script(
      'wpmc-cookie-consent',
      WPMC_PLUGIN_URL . 'public/js/wpmc-cookie-consent.js',
      array(),  // sin dependencias
      WPMC_VERSION,
      true  // en footer
  );

  // UI del banner
  wp_enqueue_script(
      'wpmc-banner-ui',
      WPMC_PLUGIN_URL . 'public/js/banner-ui.js',
      array('wpmc-cookie-consent'),  // depende del script de consentimiento
      WPMC_VERSION,
      true  // en footer
  );

  // Pasar config
  $cfg = array(
    'cookieName'     => (string) wpmc_get_option( 'consent_cookie' ),
    'policyVersion'  => (int) wpmc_get_option( 'policy_version' ),
    'waitForUpdate'  => (int) wpmc_get_option( 'wait_for_update' ),
    'isDebug'        => (bool) wpmc_get_option( 'debug' ),
    'eventUpdate'    => 'wpmc_consent_update',
  );

  wp_add_inline_script(
    'wpmc-cookie-consent',
    'window.WPMC_CONFIG = ' . wp_json_encode( $cfg ) . ';',
    'before'
  );
}, 20 );

/**
 * HEAD: Inicialización de Google Consent Mode v2
 */
add_action('wp_head', function () {
    $opts        = wpmc_get_options();
    $gtm_id      = $opts['gtm_id'];
    $cookie_name = $opts['consent_cookie'];
    $wait_ms     = (int) $opts['wait_for_update'];
    ?>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }

        // Defaults: denied (Consent Mode v2)
        gtag('consent', 'default', {
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'analytics_storage': 'denied',
            'wait_for_update': <?php echo (int) $wait_ms; ?>,
        });

        // Restore from consent cookie (si existe) antes de cargar GTM
        (() => {
        const cookieName = <?php echo wp_json_encode( $cookie_name ); ?>;

        const getCookie = (name) => {
          const parts = document.cookie ? document.cookie.split(';') : [];
          for (let i = 0; i < parts.length; i++) {
            const c = parts[i].trim();
            if (c.startsWith(name + '=')) return c.substring(name.length + 1);
          }
          return null;
        };

        const raw = getCookie(cookieName);
        if (!raw) return;

        try {
          const decoded = decodeURIComponent(raw);
          const parsed = JSON.parse(decoded);
          const prefs = parsed && parsed.prefs ? parsed.prefs : null;
          if (!prefs) return;

          gtag('consent', 'update', {
            'ad_storage': prefs.ads ? 'granted' : 'denied',
            'ad_user_data': prefs.ads ? 'granted' : 'denied',
            'ad_personalization': prefs.ads ? 'granted' : 'denied',
            'analytics_storage': prefs.analytics ? 'granted' : 'denied'
          });
        } catch(e) {
          // Error al parsear cookie: ignorar
          console.warn('WPMC: error parsing consent cookie', e);
        }
      })();
    </script>
    <?php
    
    // Carga GTM (si hay ID)
  if ( $gtm_id && $gtm_id !== 'GTM-XXXXXXX' ) : ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer',<?php echo wp_json_encode( $gtm_id ); ?>);</script>
  <?php endif;
}, 0);

/**
 * HEAD: Imprime providers bloqueados (no gestionados por GTM)
 */
add_action( 'wp_head', function () {
  $providers = wpmc_providers();
  if ( empty( $providers ) || ! is_array( $providers ) ) return;

  foreach ( $providers as $p ) {
    if ( empty( $p['category'] ) ) continue;

    if ( ( $p['type'] ?? '' ) === 'script' && ! empty( $p['src'] ) ) {
      wpmc_print_blocked_script_tag( $p['category'], $p['src'], null, $p['attrs'] ?? array() );
    }

    if ( ( $p['type'] ?? '' ) === 'inline' && ! empty( $p['code'] ) ) {
      wpmc_print_blocked_script_tag( $p['category'], null, $p['code'], $p['attrs'] ?? array() );
    }
  }
}, 1 );

/**
 * FOOTER: Banner HTML
 */
add_action('wp_footer', function () {
  $inset  = 'auto 0 0 0';
  $floatX = 'left:16px;right:auto;';
  ?>
  
  <style>
    #wpmc-banner{position:fixed;inset:<?php echo esc_attr( $inset ); ?>;z-index:99999;}
    #wpmc-preferences-btn{position:fixed;bottom:16px;<?php echo esc_attr( $floatX ); ?>z-index:99998;display:none;}
  </style>

  <div id="wpmc-banner" class="wpmc-banner" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( wpmc_text('txt_title') ); ?>">
    <div class="wpmc-content">
      <div>
        <p class="wpmc-title"><?php echo esc_html( wpmc_text('txt_title') ); ?></p>
        <div class="wpmc-desc"><?php echo esc_html( wpmc_text('txt_msg') ); ?></div>
      </div>
      <div class="wpmc-actions">
        <button id="wpmc-accept" class="wpmc-btn wpmc-btn--primary"><?php echo esc_html( wpmc_text('txt_accept') ); ?></button>
        <button id="wpmc-reject" class="wpmc-btn wpmc-btn--ghost"><?php echo esc_html( wpmc_text('txt_reject') ); ?></button>
        <button id="wpmc-manage" class="wpmc-btn wpmc-btn--link" type="button"><?php echo esc_html( wpmc_text('txt_manage') ); ?></button>
      </div>
    </div>
  </div>

  <button id="wpmc-preferences-btn" type="button" aria-label="<?php echo esc_attr( wpmc_text('txt_prefs') ); ?>">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M12 2a10 10 0 1 0 9.54 12.9A4 4 0 0 1 17 11a4 4 0 0 1-4-4 4 4 0 0 1-1-.13A4 4 0 0 1 12 2zm-3 9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm7 4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM9 18a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
    </svg>
  </button>

  <div id="wpmc-modal" class="wpmc-modal" role="dialog" hidden>
    <div class="wpmc-overlay" tabindex="-1" data-close></div>
    <div class="wpmc-box">
      <p class="wpmc-title"><?php echo esc_html( wpmc_text('txt_panel_title') ); ?></p>
      <p class="wpmc-desc"><?php echo esc_html( wpmc_text('txt_panel_desc') ); ?></p>

      <div class="wpmc-row">
        <div>
          <div class="wpmc-label"><?php echo esc_html( wpmc_text('txt_cat_analytics') ); ?></div>
          <div class="wpmc-desc"><?php echo esc_html( wpmc_text('txt_cat_analytics_desc') ); ?></div>
        </div>
        <label class="wpmc-switch-label">
          <input id="wpmc-opt-analytics" type="checkbox">
          <span class="wpmc-switch"></span>
        </label>
      </div>

      <div class="wpmc-row">
        <div>
          <div class="wpmc-label"><?php echo esc_html( wpmc_text('txt_cat_ads') ); ?></div>
          <div class="wpmc-desc"><?php echo esc_html( wpmc_text('txt_cat_ads_desc') ); ?></div>
        </div>
        <label class="wpmc-switch-label">
          <input id="wpmc-opt-marketing" type="checkbox">
          <span class="wpmc-switch"></span>
        </label>
      </div>

      <div class="wpmc-actions">
        <button id="wpmc-save" class="wpmc-btn wpmc-btn--primary"><?php echo esc_html( wpmc_text('txt_save') ); ?></button>
        <button id="wpmc-close" class="wpmc-btn wpmc-btn--ghost" type="button"><?php echo esc_html( wpmc_text('txt_close') ); ?></button>
      </div>
    </div>
  </div>
  <?php
}, 9999 );