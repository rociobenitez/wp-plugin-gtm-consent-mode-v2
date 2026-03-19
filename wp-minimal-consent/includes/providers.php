<?php
/**
 * Proveedores opcionales (solo para cosas NO gestionadas por GTM).
 *
 * @package WP_Minimal_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Devuelve providers "bloqueados" que el plugin imprimirá como <script type="text/plain"...>.
 * Por defecto vacío para proyectos GTM-only.
 *
 * Puedes extenderlo vía filtro `wpmc_providers`.
 */
function wpmc_providers() {
  $providers = array(
    // Ejemplo (descomentarlo SOLO si un proyecto no lo gestiona en GTM):
    // 'some_vendor' => array(
    //   'category' => 'analytics',
    //   'type'     => 'script',
    //   'src'      => 'https://example.com/vendor.js',
    //   'attrs'    => array( 'async' => true ),
    // ),
  );

  return apply_filters( 'wpmc_providers', $providers );
}
