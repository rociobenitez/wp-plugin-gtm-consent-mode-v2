<?php
/**
 * Plugin Name: WP Minimal Consent (GTM + Consent Mode v2)
 * Description: Banner de cookies + Google Consent Mode v2. GTM siempre, por defecto "denied", y actualiza según consentimiento.
 * Version: 0.2.0
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
define('WPMC_VERSION', '0.2.0');
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
  $gtm_id = (string) wpmc_get_option( 'gtm_id' );
  if ( ! preg_match( '/^GTM-[A-Z0-9]{1,10}$/', $gtm_id ) ) {
    $url = admin_url( 'admin.php?page=wpmc-settings' );
    echo '<div class="notice notice-error"><p><strong>WP Minimal Consent:</strong> configura tu <strong>GTM Container ID</strong> en <a href="' . esc_url( $url ) . '">Consent → Ajustes</a>.</p></div>';
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
    WPMC_VERSION
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
  // CSS personalizado de color (solo si hay valores guardados)
  $color_accept  = wpmc_get_option( 'color_accept' );
  $color_surface = wpmc_get_option( 'color_surface' );
  $custom_css    = '';

  if ( $color_accept ) {
    $c = esc_attr( $color_accept );
    $custom_css .= "#wpmc-banner.wpmc-banner--modal #wpmc-accept{background:{$c};}";
    $custom_css .= "#wpmc-banner.wpmc-banner--modal #wpmc-accept:hover{background:{$c};filter:brightness(.88);}";
    $custom_css .= ".wpmc-btn--primary{background:{$c};}";
    $custom_css .= ".wpmc-btn--primary:hover{background:{$c};filter:brightness(.88);}";
  }

  if ( $color_surface ) {
    $s = esc_attr( $color_surface );
    $custom_css .= "#wpmc-banner.wpmc-banner--bar{background:{$s};}";
    $custom_css .= "#wpmc-preferences-btn{background:{$s};}";
    $custom_css .= "#wpmc-preferences-btn:hover{background:{$s};filter:brightness(1.2);}";
  }

  if ( $custom_css ) {
    wp_add_inline_style( 'wpmc-banner-styles', $custom_css );
  }
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
  if ( $gtm_id && preg_match( '/^GTM-[A-Z0-9]{1,10}$/', $gtm_id ) ) : ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer',<?php echo wp_json_encode( $gtm_id ); ?>);</script>
  <?php endif;
}, 0);

/**
 * BODY OPEN: GTM <noscript> fallback (para navegadores sin JS)
 */
add_action( 'wp_body_open', function () {
  if ( (int) wpmc_get_option( 'noscript_by_theme' ) === 1 ) return;
  $gtm_id = (string) wpmc_get_option( 'gtm_id' );
  if ( ! $gtm_id || ! preg_match( '/^GTM-[A-Z0-9]{1,10}$/', $gtm_id ) ) return;
  ?>
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <?php
}, 0 );

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
  $policy_url   = wpmc_get_option( 'privacy_policy_url' );
  $banner_style = wpmc_get_option( 'banner_style' ) === 'modal' ? 'modal' : 'bar';
  ?>

  <div id="wpmc-banner" class="wpmc-banner wpmc-banner--<?php echo esc_attr( $banner_style ); ?>" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( wpmc_text('txt_title') ); ?>">
    <div class="wpmc-content">

      <?php if ( $banner_style === 'modal' ) : ?>
      <div class="wpmc-modal-header">
        <div class="wpmc-modal-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M224,120a40,40,0,0,1-40-40,8,8,0,0,0-8-8,40,40,0,0,1-40-40,8,8,0,0,0-8-8A104,104,0,1,0,232,128,8,8,0,0,0,224,120ZM75.51,99.51a12,12,0,1,1,0,17A12,12,0,0,1,75.51,99.51Zm25,73a12,12,0,1,1,0-17A12,12,0,0,1,100.49,172.49Zm23-40a12,12,0,1,1,17,0A12,12,0,0,1,123.51,132.49Zm41,48a12,12,0,1,1,0-17A12,12,0,0,1,164.49,180.49Z"></path></svg>
        </div>
        <p class="wpmc-title"><?php echo esc_html( wpmc_text('txt_title') ); ?></p>
        <div class="wpmc-desc">
          <?php echo esc_html( wpmc_text('txt_msg') ); ?>
          <?php if ( $policy_url ) : ?>
            <a href="<?php echo esc_url( $policy_url ); ?>" class="wpmc-policy-link" target="_blank" rel="noopener noreferrer">Más información</a>
          <?php endif; ?>
        </div>
      </div>
      <?php else : ?>
      <div>
        <p class="wpmc-title"><?php echo esc_html( wpmc_text('txt_title') ); ?></p>
        <div class="wpmc-desc">
          <?php echo esc_html( wpmc_text('txt_msg') ); ?>
          <?php if ( $policy_url ) : ?>
            <a href="<?php echo esc_url( $policy_url ); ?>" class="wpmc-policy-link" target="_blank" rel="noopener noreferrer">Más información</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="wpmc-actions">
        <button id="wpmc-accept" type="button"><?php echo esc_html( wpmc_text('txt_accept') ); ?></button>
        <button id="wpmc-reject" type="button"><?php echo esc_html( wpmc_text('txt_reject') ); ?></button>
        <button id="wpmc-manage" type="button"><?php echo esc_html( wpmc_text('txt_manage') ); ?></button>
      </div>

    </div>
  </div>

  <button id="wpmc-preferences-btn" type="button" aria-label="<?php echo esc_attr( wpmc_text('txt_prefs') ); ?>">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M12 2a10 10 0 1 0 9.54 12.9A4 4 0 0 1 17 11a4 4 0 0 1-4-4 4 4 0 0 1-1-.13A4 4 0 0 1 12 2zm-3 9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm7 4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM9 18a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
    </svg>
  </button>

  <div id="wpmc-modal" class="wpmc-modal" role="dialog" hidden>
    <div class="wpmc-overlay" tabindex="-1" data-close aria-hidden="true"></div>
    <div class="wpmc-box">
      <p id="wpmc-title" class="wpmc-title" tabindex="-1"><?php echo esc_html( wpmc_text('txt_panel_title') ); ?></p>
      <p class="wpmc-desc"><?php echo esc_html( wpmc_text('txt_panel_desc') ); ?></p>

      <div class="wpmc-row">
        <div>
          <div class="wpmc-label"><?php echo esc_html( wpmc_text('txt_cat_necessary') ); ?></div>
          <div class="wpmc-desc"><?php echo esc_html( wpmc_text('txt_cat_necessary_desc') ); ?></div>
        </div>
        <label class="wpmc-switch-label wpmc-switch-label--locked" aria-label="<?php echo esc_attr( wpmc_text('txt_always_active') ); ?>" title="<?php esc_attr_e( 'Las cookies necesarias no se pueden desactivar', 'wp-minimal-consent' ); ?>">
          <input type="checkbox" checked disabled>
          <span class="wpmc-switch"></span>
        </label>
      </div>
      <?php wpmc_render_cookie_list( 'cookies_necessary' ); ?>

      <div class="wpmc-row">
        <div>
          <div class="wpmc-label"><?php echo esc_html( wpmc_text('txt_cat_functional') ); ?></div>
          <div class="wpmc-desc"><?php echo esc_html( wpmc_text('txt_cat_functional_desc') ); ?></div>
        </div>
        <label class="wpmc-switch-label">
          <input id="wpmc-opt-functional" type="checkbox">
          <span class="wpmc-switch"></span>
        </label>
      </div>
      <?php wpmc_render_cookie_list( 'cookies_functional' ); ?>

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
      <?php wpmc_render_cookie_list( 'cookies_analytics' ); ?>

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
      <?php wpmc_render_cookie_list( 'cookies_ads' ); ?>

      <div class="wpmc-actions">
        <button id="wpmc-save" class="wpmc-btn wpmc-btn--primary"><?php echo esc_html( wpmc_text('txt_save') ); ?></button>
        <button id="wpmc-close" class="wpmc-btn wpmc-btn--text" type="button"><?php echo esc_html( wpmc_text('txt_close') ); ?></button>
      </div>
    </div>
  </div>
  <?php
}, 9999 );