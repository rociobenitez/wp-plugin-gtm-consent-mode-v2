<?php
/**
 * Funciones helper para WP Minimal Consent
 *
 * @package WP_Minimal_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Salir si se accede directamente
}

function wpmc_print_blocked_script_tag($category, $src = null, $inline = null, $attrs = []) {
  $data_type = 'text/javascript';

  // attrs HTML (async/defer/id/nonce)
  $attr_html = '';
  foreach ($attrs as $k => $v) {
    if ($v === true) {
      $attr_html .= ' ' . esc_attr($k);
    } elseif ($v !== false && $v !== null) {
      $attr_html .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
    }
  }

  echo '<script type="text/plain" data-type="' . esc_attr($data_type) . '" data-wpmc-category="' . esc_attr($category) . '"' . $attr_html;

  if ($src) {
    echo ' src="' . esc_url($src) . '"></script>';
    return;
  }

  echo '>' . $inline . '</script>';
}
