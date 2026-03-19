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
    'gtm_id'              => 'GTM-XXXXXXX',  // cambiar en cada proyecto
    'consent_cookie'      => 'wpmc_consent',
    'policy_version'      => 1,
    'wait_for_update'     => 500,
    'debug'               => 0,  // 0 = off, 1 = on
    'noscript_by_theme'   => 1,  // 1 = el tema incluye el <noscript> de GTM, el plugin no lo imprime
    'banner_style'        => 'bar',  // 'bar' | 'modal'
    'privacy_policy_url'  => '',
    'color_accept'        => '',  // hex, ej: #3b82f6 — botón Aceptar y Guardar
    'color_surface'       => '',  // hex, ej: #1e293b — fondo barra y botón flotante

    // Textos (MVP)
    'txt_title'         => 'Esta página web usa cookies',
    'txt_msg'           => 'Usamos cookies para mejorar su experiencia de navegación, mostrarle anuncios o contenidos personalizados y analizar nuestro tráfico. Al hacer clic en “Aceptar todo” usted da su consentimiento a nuestro uso de las cookies.',
    'txt_accept'        => 'Aceptar todo',
    'txt_reject'        => 'Denegar',
    'txt_manage'        => 'Gestionar',
    'txt_prefs'         => 'Preferencias de cookies',

    'txt_panel_title'        => 'Preferencias de cookies',
    'txt_panel_desc'         => 'Activa o desactiva categorías. Siempre usamos cookies necesarias.',

    // Necesarias
    'txt_cat_necessary' => 'Necesarias',
    'txt_cat_necessary_desc' => 'Las cookies necesarias son imprescindibles para el funcionamiento básico del sitio web y no pueden desactivarse.',
    'txt_always_active' => 'Siempre activo',

    // Funcionales
    'txt_cat_functional' => 'Funcionales',
    'txt_cat_functional_desc' => 'Las cookies funcionales permiten que el sitio web recuerde las elecciones que has hecho (como tu nombre de usuario, idioma o la región en la que te encuentras) y proporcionan características mejoradas y más personales.',

    // Analítica
    'txt_cat_analytics' => 'Analítica',
    'txt_cat_analytics_desc' => 'Las cookies analíticas se utilizan para comprender cómo interactúan los visitantes con el sitio web. Estas cookies ayudan a proporcionar información sobre métricas el número de visitantes, el porcentaje de rebote, la fuente de tráfico, etc.',

    // Publicidad
    'txt_cat_ads' => 'Publicidad',
    'txt_cat_ads_desc' => 'Las cookies de publicidad se utilizan para mostrar anuncios personalizados y compartir datos con terceros (Google Ads, Facebook Ads...).',
    'txt_save' => 'Guardar selección',
    'txt_close' => 'Cerrar',

    // Lista de cookies por categoría
    // Formato por línea: nombre | propósito | duración | tipo | URL (opcional)
    // Las líneas que empiezan por # son comentarios y no se muestran al usuario.
    'cookies_necessary' => "wpmc_consent | Almacena las preferencias de consentimiento del usuario | 1 año | HTTP Cookie",

    'cookies_functional' => '',

    'cookies_analytics' => implode( "\n", array(
      '# Sustituye XXXXXXXXXX por el sufijo de tu Measurement ID de GA4.',
      '# Ejemplo: si tu ID es G-ABC12345, el sufijo es ABC12345.',
      '_ga | Google Analytics — identifica visitantes únicos entre sesiones | 2 años | HTTP Cookie | https://policies.google.com/privacy',
      '_gid | Google Analytics — distingue sesiones del mismo visitante | 24 horas | HTTP Cookie | https://policies.google.com/privacy',
      '_ga_XXXXXXXXXX | Google Analytics 4 — estado de sesión y seguimiento de campaña | 2 años | HTTP Cookie | https://policies.google.com/privacy',
    ) ),

    'cookies_ads' => implode( "\n", array(
      '# Elimina esta línea si el sitio no utiliza Google Ads.',
      '_gcl_au | Google Ads — seguimiento de conversiones de anuncios | 3 meses | HTTP Cookie | https://policies.google.com/privacy',
    ) ),
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
