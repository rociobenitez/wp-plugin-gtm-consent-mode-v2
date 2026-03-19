<?php
/**
 * Funciones helper para WP Minimal Consent
 *
 * @package WP_Minimal_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Salir si se accede directamente
}

/**
 * Parsea el campo de texto de cookies en un array de entradas.
 * Formato por línea: nombre | propósito | duración | tipo | url (opcional)
 * Las líneas vacías y las que empiezan por # se ignoran.
 */
function wpmc_parse_cookies( $raw ) {
  if ( empty( $raw ) ) return array();

  $cookies = array();
  $lines   = explode( "\n", $raw );

  foreach ( $lines as $line ) {
    $line = trim( $line );
    if ( $line === '' || str_starts_with( $line, '#' ) ) continue;

    $parts     = array_map( 'trim', explode( '|', $line ) );
    $cookies[] = array(
      'name'     => $parts[0] ?? '',
      'desc'     => $parts[1] ?? '',
      'duration' => $parts[2] ?? '',
      'type'     => $parts[3] ?? '',
      'url'      => $parts[4] ?? '',
    );
  }

  return $cookies;
}

/**
 * Renderiza el bloque colapsable "Ver cookies" para una categoría.
 * No imprime nada si no hay cookies definidas.
 */
function wpmc_render_cookie_list( $option_key ) {
  $cookies = wpmc_parse_cookies( wpmc_get_option( $option_key ) );

  if ( empty( $cookies ) ) return;

  $count = count( $cookies );
  ?>
  <button type="button" class="wpmc-cookies-toggle" aria-expanded="false">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M6 9l6 6 6-6"/>
    </svg>
    Ver cookies (<?php echo (int) $count; ?>)
  </button>
  <div class="wpmc-cookies-list" hidden>
    <table class="wpmc-cookies-table">
      <thead>
        <tr>
          <th>Cookie</th>
          <th>Propósito</th>
          <th class="col-duration">Duración</th>
          <th class="col-type">Tipo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $cookies as $cookie ) : ?>
        <tr>
          <td>
            <?php if ( ! empty( $cookie['url'] ) ) : ?>
              <a href="<?php echo esc_url( $cookie['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $cookie['name'] ); ?></a>
            <?php else : ?>
              <?php echo esc_html( $cookie['name'] ); ?>
            <?php endif; ?>
          </td>
          <td><?php echo esc_html( $cookie['desc'] ); ?></td>
          <td class="col-duration"><?php echo esc_html( $cookie['duration'] ); ?></td>
          <td class="col-type"><?php echo esc_html( $cookie['type'] ); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
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
