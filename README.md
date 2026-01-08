# WP Minimal Consent (GTM + Consent Mode v2)

**WP Minimal Consent** es un **plugin CMP (Consent Management Platform) minimalista para WordPress**, orientado a **desarrolladores**, que implementa correctamente **Google Consent Mode v2** y actúa como **intermediario técnico entre el usuario y Google Tag Manager (GTM)**.

El plugin **no dispara tags**, **no gestiona proveedores desde PHP** ni reemplaza a GTM.
Su función es **recoger el consentimiento del usuario, persistirlo de forma segura y comunicarlo correctamente a GTM** siguiendo las **mejores prácticas recomendadas por Google y GDPR (2026)**.

## Objetivo del plugin

- Proveer una **base sólida, ligera y controlable por código** para:

  - Banner de cookies + panel granular
  - Consent Mode v2 (Advanced Mode)
  - Integración limpia con **Google Tag Manager**

- Evitar soluciones “black box” y **delegar la lógica de proveedores y tags en GTM**, donde debe estar.

## Filosofía

- **GTM siempre cargado**, pero con todo en `denied` por defecto
- **Consent Mode v2 es la fuente de verdad**
- **El plugin no decide qué scripts cargar**, solo:
  - Recoge el consentimiento
  - Lo guarda (cookie 1ª parte)
  - Emite señales estándar (`gtag` + `dataLayer`)
- **Sin dependencias externas**
- **Sin bloat**
- **Pensado para proyectos reales con SEO / Analytics / Ads**

## Características

### Consentimiento

- Implementación correcta de **Google Consent Mode v2**:

  - `analytics_storage`
  - `ad_storage`
  - `ad_user_data`
  - `ad_personalization`

- **Advanced Mode** (valores por defecto antes de cargar GTM)
- Actualización dinámica del consentimiento tras interacción del usuario
- Evento estándar en `dataLayer`: `wpmc_consent_update`

### Banner de cookies

- Banner inicial con:

  - Aceptar todo
  - Rechazar todo
  - Gestionar preferencias

- Panel granular:

  - Necesarias (siempre activas)
  - Analítica
  - Marketing / Publicidad

- Botón flotante para reabrir preferencias
- Accesible y sin dependencias JS externas

### Persistencia

- **Cookie de primera parte**
- Incluye:

  - versión de la política
  - timestamp
  - categorías aceptadas

- No usa base de datos
- No usa localStorage

### Desarrollo

- Configuración mediante **Settings API de WordPress**
- Logs de depuración opcionales (`WPMC_DEBUG`)
- API JS mínima para integraciones avanzadas
- Código modular y extensible

## Qué NO hace este plugin

Es importante dejarlo claro:

- No bloquea Google Tag Manager
- No dispara Google Analytics, Ads, Meta, etc.
- No decide qué proveedor se carga
- No gestiona tags de terceros fuera de GTM
- No realiza geolocalización por IP

> **Todo lo relacionado con proveedores y tags se gestiona en GTM**, como recomienda Google.

## Requisitos

- WordPress **5.9+**
- PHP **7.4+**
- **Google Tag Manager (Web container)** correctamente configurado
- Conocimientos básicos de GTM y Consent Mode

_(Opcional para testing: GA4 / Ads / Clarity / etc.)_

## Instalación

1. Copia la carpeta `wp-minimal-consent` en:

   ```
   wp-content/plugins/
   ```

2. Activa el plugin en **WP → Plugins**
3. Ve a **Ajustes → WP Minimal Consent**
4. Configura:

   - ID de Google Tag Manager
   - Textos del banner
   - Opciones básicas

## Flujo de funcionamiento (arquitectura)

### 1. Antes de cargar GTM (`<head>`)

- Se inicializa `dataLayer`
- Se ejecuta:

  ```js
  gtag("consent", "default", {
    analytics_storage: "denied",
    ad_storage: "denied",
    ad_user_data: "denied",
    ad_personalization: "denied",
    wait_for_update: 500,
  });
  ```

> Esto cumple GDPR y las recomendaciones oficiales de Google.

### 2. Carga de Google Tag Manager

- GTM **siempre se carga**
- Todos los tags quedan **bloqueados por consentimiento**

### 3. Interacción del usuario

- El usuario acepta, rechaza o configura categorías
- El consentimiento se guarda en una **cookie de primera parte**
- Se ejecuta:

  ```js
  gtag('consent', 'update', {...});
  ```

- Se emite el evento:

  ```js
  dataLayer.push({ event: "wpmc_consent_update" });
  ```

### 4. Google Tag Manager actúa

- GTM decide:

  - qué tags se disparan
  - cuándo
  - bajo qué condiciones

- El plugin **no interfiere**

## Desarrollo y depuración

- Activar logs:
  - En `/includes/config.php`, establecer `debug` a `1`
- Revisar en DevTools:
  - `document.cookie`
  - `dataLayer`
  - llamadas a `gtag('consent', ...)`
- Usar **GTM Preview Mode** para validar disparos

## Integración con Google Tag Manager

Consulta la guía detallada: [`gtm_instructions.md`](gtm_instructions.md)

Incluye:

- Configuración de Consent Overview
- Variables de estado de consentimiento
- Triggers recomendados
- Manejo de Analytics, Ads y terceros (Clarity, Meta, etc.)

## Cumplimiento GDPR

- Denegación por defecto
- Consentimiento explícito
- Separación clara de finalidades
- Persistencia auditable
- Arquitectura alineada con:

  - Google Consent Mode v2
  - Recomendaciones EDPB
  - Buenas prácticas CMP 2026

> ⚠️ El cumplimiento legal final depende de la correcta configuración de GTM y del contenido legal del sitio.
