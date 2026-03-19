<?php
/**
 * Admin settings para WP Minimal Consent
 *
 * @package WP_Minimal_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menú principal del admin.
 */
add_action( 'admin_menu', function () {
  add_menu_page(
    'WP Minimal Consent',
    'Consent',
    'manage_options',
    'wpmc-settings',
    'wpmc_render_settings_page',
    'dashicons-shield',
    75
  );
} );

/**
 * Enqueue CSS solo en la página del plugin.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
  if ( $hook !== 'toplevel_page_wpmc-settings' ) return;

  wp_enqueue_style(
    'wpmc-admin',
    WPMC_PLUGIN_URL . 'admin/css/admin.css',
    array(),
    WPMC_VERSION
  );

  wp_enqueue_script(
    'wpmc-admin',
    WPMC_PLUGIN_URL . 'admin/js/admin.js',
    array(),
    WPMC_VERSION,
    true
  );
} );

/**
 * Registro del setting y sanitización.
 */
add_action( 'admin_init', function () {
  register_setting(
    'wpmc_settings_group',
    WPMC_OPTION_NAME,
    array(
      'type'              => 'array',
      'sanitize_callback' => 'wpmc_sanitize_options',
      'default'           => wpmc_defaults(),
    )
  );
} );

/**
 * Sanitización de todas las opciones.
 */
function wpmc_sanitize_options( $input ) {
  $defaults = wpmc_defaults();
  $output   = array();

  $input = is_array( $input ) ? $input : array();

  // Privacy Policy URL
  $output['privacy_policy_url'] = isset( $input['privacy_policy_url'] )
    ? esc_url_raw( $input['privacy_policy_url'] )
    : '';

  // Colores personalizados (hex o vacío)
  $output['color_accept']  = sanitize_hex_color( $input['color_accept'] ?? '' ) ?? '';
  $output['color_surface'] = sanitize_hex_color( $input['color_surface'] ?? '' ) ?? '';

  // Banner style
  $allowed_styles         = array( 'bar', 'modal' );
  $output['banner_style'] = ( isset( $input['banner_style'] ) && in_array( $input['banner_style'], $allowed_styles, true ) )
    ? $input['banner_style']
    : 'bar';

  // GTM ID — solo se acepta el formato GTM-XXXXXXX
  if ( isset( $input['gtm_id'] ) ) {
    $gtm_candidate    = strtoupper( sanitize_text_field( $input['gtm_id'] ) );
    $output['gtm_id'] = preg_match( '/^GTM-[A-Z0-9]{1,10}$/', $gtm_candidate )
      ? $gtm_candidate
      : $defaults['gtm_id'];
  } else {
    $output['gtm_id'] = $defaults['gtm_id'];
  }

  // Cookie name (fijo al default, preparado para futura personalización)
  $output['consent_cookie'] = $defaults['consent_cookie'];

  // Policy version
  $output['policy_version'] = isset( $input['policy_version'] )
    ? max( 1, (int) $input['policy_version'] )
    : (int) $defaults['policy_version'];

  // wait_for_update
  $output['wait_for_update'] = isset( $input['wait_for_update'] )
    ? max( 0, (int) $input['wait_for_update'] )
    : (int) $defaults['wait_for_update'];

  // noscript_by_theme
  $output['noscript_by_theme'] = ! empty( $input['noscript_by_theme'] ) ? 1 : 0;

  // Debug
  $output['debug'] = ! empty( $input['debug'] ) ? 1 : 0;

  // Textos — todos los que empiezan por txt_
  $text_keys = array_keys( $defaults );
  foreach ( $text_keys as $k ) {
    if ( str_starts_with( $k, 'txt_' ) ) {
      $output[ $k ] = isset( $input[ $k ] )
        ? sanitize_textarea_field( $input[ $k ] )
        : $defaults[ $k ];
    }
  }

  // Lista de cookies por categoría
  $cookie_keys = array( 'cookies_necessary', 'cookies_functional', 'cookies_analytics', 'cookies_ads' );
  foreach ( $cookie_keys as $k ) {
    $output[ $k ] = isset( $input[ $k ] )
      ? sanitize_textarea_field( $input[ $k ] )
      : '';
  }

  return $output;
}

/**
 * Render de la página de ajustes con layout de cards.
 */
function wpmc_render_settings_page() {
  if ( ! current_user_can( 'manage_options' ) ) return;
  ?>
  <div class="wrap wpmc-admin">

    <div class="wpmc-admin-header">
      <div class="wpmc-admin-header__icon">
        <span class="dashicons dashicons-shield"></span>
      </div>
      <div>
        <h1 class="wpmc-admin-header__title">WP Minimal Consent</h1>
        <p class="wpmc-admin-header__sub">Google Consent Mode v2 + GTM</p>
      </div>
      <span class="wpmc-admin-header__badge">v<?php echo esc_html( WPMC_VERSION ); ?></span>
    </div>

    <form method="post" action="options.php">
      <?php settings_fields( 'wpmc_settings_group' ); ?>

      <?php /* ---- Card: Configuración ---- */ ?>
      <div class="wpmc-card">
        <div class="wpmc-card__header">
          <span class="dashicons dashicons-admin-settings"></span>
          Configuración
        </div>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">URL Política de privacidad</th>
              <td><?php wpmc_field_url( array(
                'key'         => 'privacy_policy_url',
                'placeholder' => 'https://tudominio.com/politica-de-privacidad',
                'help'        => 'Se muestra como enlace en el banner. Requerido por RGPD.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Estilo del banner</th>
              <td><?php wpmc_field_select( array(
                'key'     => 'banner_style',
                'options' => array(
                  'bar'   => 'Barra inferior (sutil)',
                  'modal' => 'Modal centrado (bloqueante)',
                ),
                'help'    => 'Modal recomendado para mayor visibilidad y cumplimiento del RGPD.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">GTM Container ID</th>
              <td><?php wpmc_field_text( array(
                'key'         => 'gtm_id',
                'placeholder' => 'GTM-XXXXXXX',
                'help'        => 'Ejemplo: GTM-ABCDE12',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Versión de política</th>
              <td><?php wpmc_field_number( array(
                'key'  => 'policy_version',
                'min'  => 1,
                'step' => 1,
                'help' => 'Incrementa para forzar re-consent cuando cambies textos o proveedores.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">wait_for_update (ms)</th>
              <td><?php wpmc_field_number( array(
                'key'  => 'wait_for_update',
                'min'  => 0,
                'step' => 50,
                'help' => 'Tiempo que las tags de Google esperan al CMP antes de enviar datos.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">El tema incluye el &lt;noscript&gt; de GTM</th>
              <td><?php wpmc_field_checkbox( array(
                'key'  => 'noscript_by_theme',
                'help' => 'Activa si tu tema o un snippet ya incluye el tag &lt;noscript&gt; de GTM tras &lt;body&gt;. Si lo desactivas, el plugin lo inyecta automáticamente.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Debug</th>
              <td><?php wpmc_field_checkbox( array(
                'key'  => 'debug',
                'help' => 'Activa logs en consola (solo para desarrollo).',
              ) ); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php /* ---- Card: Apariencia ---- */ ?>
      <div class="wpmc-card">
        <div class="wpmc-card__header">
          <span class="dashicons dashicons-art"></span>
          Apariencia
        </div>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">Color botón Aceptar</th>
              <td><?php wpmc_field_color( array(
                'key'     => 'color_accept',
                'default' => '#111111',
                'help'    => 'Color de fondo del botón "Aceptar todo" y del botón "Guardar" en el panel. Dejar sin color usa el negro por defecto.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Color de fondo del banner</th>
              <td><?php wpmc_field_color( array(
                'key'     => 'color_surface',
                'default' => '#1a1a1a',
                'help'    => 'Color de fondo de la barra inferior y del botón flotante de preferencias. Dejar sin color usa el negro por defecto.',
              ) ); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php /* ---- Card: Textos del banner ---- */ ?>
      <div class="wpmc-card">
        <div class="wpmc-card__header">
          <span class="dashicons dashicons-megaphone"></span>
          Textos del banner
        </div>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">Título</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_title' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Mensaje</th>
              <td><?php wpmc_field_textarea( array( 'key' => 'txt_msg' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Botón: Aceptar todo</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_accept' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Botón: Rechazar</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_reject' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Botón: Gestionar</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_manage' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Botón flotante (aria-label)</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_prefs' ) ); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php /* ---- Card: Textos del panel de preferencias ---- */ ?>
      <div class="wpmc-card">
        <div class="wpmc-card__header">
          <span class="dashicons dashicons-list-view"></span>
          Textos del panel de preferencias
        </div>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">Título del panel</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_panel_title' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Descripción del panel</th>
              <td><?php wpmc_field_textarea( array( 'key' => 'txt_panel_desc' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Categoría: Necesarias</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_cat_necessary' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Descripción: Necesarias</th>
              <td><?php wpmc_field_textarea( array( 'key' => 'txt_cat_necessary_desc' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Badge "Siempre activo"</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_always_active' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Categoría: Funcionales</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_cat_functional' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Descripción: Funcionales</th>
              <td><?php wpmc_field_textarea( array( 'key' => 'txt_cat_functional_desc' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Categoría: Analítica</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_cat_analytics' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Descripción: Analítica</th>
              <td><?php wpmc_field_textarea( array( 'key' => 'txt_cat_analytics_desc' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Categoría: Publicidad</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_cat_ads' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Descripción: Publicidad</th>
              <td><?php wpmc_field_textarea( array( 'key' => 'txt_cat_ads_desc' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Botón: Guardar selección</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_save' ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Botón: Cerrar</th>
              <td><?php wpmc_field_text( array( 'key' => 'txt_close' ) ); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php /* ---- Card: Lista de cookies ---- */ ?>
      <div class="wpmc-card">
        <div class="wpmc-card__header">
          <span class="dashicons dashicons-category"></span>
          Lista de cookies
        </div>
        <p class="wpmc-card__desc">
          Escribe <strong>una cookie por línea</strong> con el formato:<br>
          <code class="cookie-format">nombre | propósito | duración | tipo | URL política privacidad (opcional)</code><br><br>
          Las líneas que empiezan por <code>#</code> son comentarios y <strong>no se muestran</strong> al usuario del sitio web.<br>
          Si una categoría no aplica al proyecto, deja el campo vacío — no aparecerá en el panel de cookies.
        </p>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">Necesarias</th>
              <td><?php wpmc_field_textarea( array(
                'key'  => 'cookies_necessary',
                'help' => 'Cookies imprescindibles para el funcionamiento del sitio. No requieren consentimiento pero deben listarse por transparencia.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Funcionales</th>
              <td><?php wpmc_field_textarea( array(
                'key'  => 'cookies_functional',
                'help' => 'Cookies que mejoran la experiencia: idioma, región, preferencias de interfaz, chat en vivo, etc. Varía según el proyecto — añade solo las que uses.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Analítica</th>
              <td><?php wpmc_field_textarea( array(
                'key'  => 'cookies_analytics',
                'help' => '⚠ Sustituye XXXXXXXXXX en _ga_XXXXXXXXXX por el sufijo de tu Measurement ID de GA4. Ejemplo: si tu ID es G-ABC12345, escribe _ga_ABC12345.',
              ) ); ?></td>
            </tr>
            <tr>
              <th scope="row">Publicidad</th>
              <td><?php wpmc_field_textarea( array(
                'key'  => 'cookies_ads',
                'help' => 'Cookies de seguimiento publicitario. Si el sitio no usa Google Ads u otras plataformas de anuncios, deja este campo vacío.',
              ) ); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php submit_button( 'Guardar cambios' ); ?>
    </form>

  </div>
  <?php
}

/* =====================================================
   Field renderers
   ===================================================== */

function wpmc_field_text( $args ) {
  $opts = wpmc_get_options();
  $key  = $args['key'];
  $val  = $opts[ $key ] ?? '';
  $ph   = $args['placeholder'] ?? '';
  $help = $args['help'] ?? '';

  printf(
    '<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" placeholder="%4$s"/>',
    esc_attr( WPMC_OPTION_NAME ),
    esc_attr( $key ),
    esc_attr( $val ),
    esc_attr( $ph )
  );

  if ( $help ) {
    echo '<p class="description">' . esc_html( $help ) . '</p>';
  }
}

function wpmc_field_textarea( $args ) {
  $opts = wpmc_get_options();
  $key  = $args['key'];
  $val  = $opts[ $key ] ?? '';
  $help = $args['help'] ?? '';

  printf(
    '<textarea class="large-text" rows="3" name="%1$s[%2$s]">%3$s</textarea>',
    esc_attr( WPMC_OPTION_NAME ),
    esc_attr( $key ),
    esc_textarea( $val )
  );

  if ( $help ) {
    echo '<p class="description">' . esc_html( $help ) . '</p>';
  }
}

function wpmc_field_number( $args ) {
  $opts = wpmc_get_options();
  $key  = $args['key'];
  $val  = (int) ( $opts[ $key ] ?? 0 );
  $min  = isset( $args['min'] ) ? (int) $args['min'] : 0;
  $step = isset( $args['step'] ) ? (int) $args['step'] : 1;
  $help = $args['help'] ?? '';

  printf(
    '<input type="number" name="%1$s[%2$s]" value="%3$d" min="%4$d" step="%5$d"/>',
    esc_attr( WPMC_OPTION_NAME ),
    esc_attr( $key ),
    $val,
    $min,
    $step
  );

  if ( $help ) {
    echo '<p class="description">' . esc_html( $help ) . '</p>';
  }
}

function wpmc_field_checkbox( $args ) {
  $opts = wpmc_get_options();
  $key  = $args['key'];
  $val  = ! empty( $opts[ $key ] );
  $help = $args['help'] ?? '';

  printf(
    '<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s/> %4$s</label>',
    esc_attr( WPMC_OPTION_NAME ),
    esc_attr( $key ),
    checked( $val, true, false ),
    esc_html( $help )
  );
}

function wpmc_field_select( $args ) {
  $opts    = wpmc_get_options();
  $key     = $args['key'];
  $current = $opts[ $key ] ?? '';
  $options = $args['options'] ?? array();
  $help    = $args['help'] ?? '';

  printf(
    '<select name="%1$s[%2$s]">',
    esc_attr( WPMC_OPTION_NAME ),
    esc_attr( $key )
  );

  foreach ( $options as $value => $label ) {
    printf(
      '<option value="%1$s" %2$s>%3$s</option>',
      esc_attr( $value ),
      selected( $current, $value, false ),
      esc_html( $label )
    );
  }

  echo '</select>';

  if ( $help ) {
    echo '<p class="description">' . esc_html( $help ) . '</p>';
  }
}

function wpmc_field_color( $args ) {
  $opts        = wpmc_get_options();
  $key         = $args['key'];
  $saved       = $opts[ $key ] ?? '';          // '' o '#xxxxxx'
  $default_hex = $args['default'] ?? '#000000'; // valor visual cuando no hay guardado
  $help        = $args['help'] ?? '';
  $is_active   = $saved !== '';
  $picker_val  = $is_active ? $saved : $default_hex;

  printf(
    '<div class="wpmc-color-wrap%1$s">
      <input type="hidden" class="wpmc-color-hidden" name="%2$s[%3$s]" value="%4$s"/>
      <input type="color" class="wpmc-color-picker" value="%5$s" data-default="%6$s" aria-label="%7$s"/>
      <span class="wpmc-color-swatch" style="%8$s"></span>
      <button type="button" class="button button-small wpmc-color-reset"%9$s>Restablecer</button>
    </div>',
    $is_active ? ' wpmc-color--active' : '',
    esc_attr( WPMC_OPTION_NAME ),
    esc_attr( $key ),
    esc_attr( $saved ),
    esc_attr( $picker_val ),
    esc_attr( $default_hex ),
    esc_attr( $args['label'] ?? $key ),
    $is_active ? 'background:' . esc_attr( $saved ) . ';' : '',
    $is_active ? '' : ' disabled'
  );

  if ( $help ) {
    echo '<p class="description">' . esc_html( $help ) . '</p>';
  }
}

function wpmc_field_url( $args ) {
  $opts = wpmc_get_options();
  $key  = $args['key'];
  $val  = $opts[ $key ] ?? '';
  $ph   = $args['placeholder'] ?? '';
  $help = $args['help'] ?? '';

  printf(
    '<input type="url" class="regular-text" name="%1$s[%2$s]" value="%3$s" placeholder="%4$s"/>',
    esc_attr( WPMC_OPTION_NAME ),
    esc_attr( $key ),
    esc_attr( $val ),
    esc_attr( $ph )
  );

  if ( $help ) {
    echo '<p class="description">' . esc_html( $help ) . '</p>';
  }
}
