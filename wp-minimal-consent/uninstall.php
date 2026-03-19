<?php
/**
 * Se ejecuta cuando el plugin se elimina desde el panel de WordPress
 * (Plugins → Borrar). Limpia todas las opciones guardadas en la BD.
 *
 * No se llama al desactivar — solo al eliminar definitivamente.
 *
 * @package WP_Minimal_Consent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpmc_options' );
