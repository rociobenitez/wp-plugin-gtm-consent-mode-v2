<?php
/**
 * Admin settings para WP Minimal Consent
 *
 * @package WP_Minimal_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_menu', function () {
  add_options_page(
    'WP Minimal Consent',
    'WP Minimal Consent',
    'manage_options',
    'wpmc-settings',
    'wpmc_render_settings_page'
  );
} );

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

  add_settings_section(
    'wpmc_section_general',
    'Ajustes generales',
    function () {
      echo '<p>Configura GTM y el comportamiento básico del banner.</p>';
    },
    'wpmc-settings'
  );

  // GTM ID
  add_settings_field(
    'wpmc_gtm_id',
    'GTM Container ID',
    'wpmc_field_text',
    'wpmc-settings',
    'wpmc_section_general',
    array(
      'key'         => 'gtm_id',
      'placeholder' => 'GTM-XXXXXXX',
      'help'        => 'Ejemplo: GTM-ABCDE12',
    )
  );

  // Policy version
  add_settings_field(
    'wpmc_policy_version',
    'Versión de política',
    'wpmc_field_number',
    'wpmc-settings',
    'wpmc_section_general',
    array(
      'key'   => 'policy_version',
      'min'   => 1,
      'step'  => 1,
      'help'  => 'Incrementa para forzar re-consent cuando cambies textos/proveedores.',
    )
  );

  // wait_for_update
  add_settings_field(
    'wpmc_wait_for_update',
    'wait_for_update (ms)',
    'wpmc_field_number',
    'wpmc-settings',
    'wpmc_section_general',
    array(
      'key'   => 'wait_for_update',
      'min'   => 0,
      'step'  => 50,
      'help'  => 'Tiempo que Google tags esperan antes de enviar datos si el CMP carga asíncrono.',
    )
  );

  // debug
  add_settings_field(
    'wpmc_debug',
    'Debug',
    'wpmc_field_checkbox',
    'wpmc-settings',
    'wpmc_section_general',
    array(
      'key'  => 'debug',
      'help' => 'Activa logs en consola (solo para desarrollo).',
    )
  );

  // Textos (MVP)
  add_settings_section(
    'wpmc_section_texts',
    'Textos del banner',
    function () {
      echo '<p>Textos básicos sin entrar aún en i18n avanzado.</p>';
    },
    'wpmc-settings'
  );

  $text_fields = array(
    'txt_title'       => 'Título',
    'txt_msg'         => 'Mensaje',
    'txt_accept'      => 'Botón: Aceptar todo',
    'txt_reject'      => 'Botón: Rechazar',
    'txt_manage'      => 'Botón: Gestionar',
    'txt_panel_title' => 'Título panel',
    'txt_panel_desc'  => 'Descripción panel',
    'txt_save'        => 'Botón: Guardar selección',
    'txt_close'       => 'Botón: Cerrar',
  );

  foreach ( $text_fields as $key => $label ) {
    add_settings_field(
      'wpmc_' . $key,
      $label,
      'wpmc_field_textarea_or_text',
      'wpmc-settings',
      'wpmc_section_texts',
      array(
        'key' => $key,
      )
    );
  }
} );

/**
 * Sanitización (MVP).
 */
function wpmc_sanitize_options( $input ) {
  $defaults = wpmc_defaults();
  $output   = array();

  $input = is_array( $input ) ? $input : array();

  // GTM ID
  $output['gtm_id'] = isset( $input['gtm_id'] )
    ? sanitize_text_field( $input['gtm_id'] )
    : $defaults['gtm_id'];

  // Cookie name (por ahora fijo al default, lo dejamos preparado)
  $output['consent_cookie'] = $defaults['consent_cookie'];

  // Policy version
  $output['policy_version'] = isset( $input['policy_version'] )
    ? max( 1, (int) $input['policy_version'] )
    : (int) $defaults['policy_version'];

  // wait_for_update
  $output['wait_for_update'] = isset( $input['wait_for_update'] )
    ? max( 0, (int) $input['wait_for_update'] )
    : (int) $defaults['wait_for_update'];

  // debug
  $output['debug'] = ! empty( $input['debug'] ) ? 1 : 0;

  // Textos
  $text_keys = array_keys( $defaults );
  foreach ( $text_keys as $k ) {
    if ( str_starts_with( $k, 'txt_' ) ) {
      if ( isset( $input[ $k ] ) ) {
        // MVP: texto plano. (Si quieres HTML permitido, lo abrimos luego con wp_kses.)
        $output[ $k ] = sanitize_textarea_field( $input[ $k ] );
      } else {
        $output[ $k ] = $defaults[ $k ];
      }
    }
  }

  return $output;
}

/**
 * Render Settings Page.
 */
function wpmc_render_settings_page() {
  if ( ! current_user_can( 'manage_options' ) ) return;

  echo '<div class="wrap">';
  echo '<h1>WP Minimal Consent</h1>';
  echo '<form method="post" action="options.php">';

  settings_fields( 'wpmc_settings_group' );
  do_settings_sections( 'wpmc-settings' );
  submit_button();

  echo '</form>';
  echo '</div>';
}

/** Renderers simples de fields */
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

function wpmc_field_textarea_or_text( $args ) {
  $opts = wpmc_get_options();
  $key  = $args['key'];
  $val  = $opts[ $key ] ?? '';

  $is_long = in_array( $key, array( 'txt_msg', 'txt_panel_desc' ), true );

  if ( $is_long ) {
    printf(
      '<textarea class="large-text" rows="3" name="%1$s[%2$s]">%3$s</textarea>',
      esc_attr( WPMC_OPTION_NAME ),
      esc_attr( $key ),
      esc_textarea( $val )
    );
  } else {
    printf(
      '<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s"/>',
      esc_attr( WPMC_OPTION_NAME ),
      esc_attr( $key ),
      esc_attr( $val )
    );
  }
}