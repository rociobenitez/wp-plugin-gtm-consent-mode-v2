<?php
/**
 * Config y helpers de opciones para WP Minimal Consent
 *
 * @package WP_Minimal_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

const WPMC_OPTION_NAME = 'wpmc_options';

/**
 * Defaults del plugin (MVP).
 */
function wpmc_defaults() {
  return array(
    'gtm_id'            => 'GTM-XXXXXXX',  // cambiar en cada proyecto
    'consent_cookie'    => 'wpmc_consent',
    'policy_version'    => 1,
    'wait_for_update'   => 500,
    'debug'             => 0,  // 0 = off, 1 = on

    // Textos (MVP)
    'txt_title'         => 'Valoramos tu privacidad',
    'txt_msg'           => 'Usamos cookies para mejorar su experiencia de navegación, mostrarle anuncios o contenidos personalizados y analizar nuestro tráfico. Al hacer clic en “Aceptar todo” usted da su consentimiento a nuestro uso de las cookies.',
    'txt_accept'        => 'Aceptar todo',
    'txt_reject'        => 'Rechazar',
    'txt_manage'        => 'Gestionar',
    'txt_prefs'         => 'Preferencias de cookies',

    'txt_panel_title'        => 'Preferencias de cookies',
    'txt_panel_desc'         => 'Activa o desactiva categorías. Siempre usamos cookies necesarias.',
    'txt_cat_analytics'      => 'Analítica',
    'txt_cat_analytics_desc' => 'Las cookies analíticas se utilizan para comprender cómo interactúan los visitantes con el sitio web. Estas cookies ayudan a proporcionar información sobre métricas el número de visitantes, el porcentaje de rebote, la fuente de tráfico, etc.',
    'txt_cat_ads'            => 'Publicidad',
    'txt_cat_ads_desc'       => 'Las cookies de publicidad se utilizan para mostrar anuncios personalizados y compartir datos con terceros (Google Ads, Facebook Ads...).',
    'txt_save'               => 'Guardar selección',
    'txt_close'              => 'Cerrar',
  );
}

/**
 * Devuelve todas las opciones fusionadas con defaults.
 */
function wpmc_get_options() {
  $defaults = wpmc_defaults();
  $stored   = get_option( WPMC_OPTION_NAME, array() );

  if ( ! is_array( $stored ) ) {
    $stored = array();
  }

  return array_merge( $defaults, $stored );
}

/**
 * Getter simple de opción.
 */
function wpmc_get_option( $key ) {
  $opts = wpmc_get_options();
  return $opts[ $key ] ?? null;
}

/**
 * Helper para textos (por claridad en templates).
 */
function wpmc_text( $key ) {
  $val = wpmc_get_option( $key );
  return is_string( $val ) ? $val : '';
}
