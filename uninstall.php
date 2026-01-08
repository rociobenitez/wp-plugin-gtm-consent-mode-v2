<?php
/**
 * Uninstall WP Minimal Consent
 *
 * Se ejecuta SOLO cuando el plugin es eliminado (no al desactivarlo).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Opciones del plugin (Settings API)
$option_names = [
    'wpmc_settings',
];

// Borrar opciones
foreach ( $option_names as $option ) {
    delete_option( $option );
    delete_site_option( $option ); // Por si se usó en multisite
}
